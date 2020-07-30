<?php
namespace OCA\WrikeSync\Wrike;

use OCA\WrikeSync\AppInfo\AppLogger;
use OCA\WrikeSync\AppInfo\HttpCommunicator;
use OCA\WrikeSync\Db\ConfigParameter;
use OCA\WrikeSync\Service\ConfigParameterService;
use OCP\ILogger;

class WrikeAPIController
{

    private $api_protocol;
    private $api_host;
    private $api_port;
    private $api_path;

    private $api_full_url;

    private $permanentAuthToken;

    private $logger;

    private $requestCounter;

    function __construct(ConfigParameterService $parameter, ILogger $Logger)
    {
        $this->api_protocol = $parameter->findValueForKey(ConfigParameter::$KEY_WRIKE_API_PROTOCOL);
        $this->api_host = $parameter->findValueForKey(ConfigParameter::$KEY_WRIKE_API_HOST);
        $this->api_port = $parameter->findValueForKey(ConfigParameter::$KEY_WRIKE_API_PORT);
        $this->api_path = $parameter->findValueForKey(ConfigParameter::$KEY_WRIKE_API_PATH);

        $this->permanentAuthToken = $parameter->findValueForKey(ConfigParameter::$KEY_WRIKE_API_AUTH_TOKEN);

        //Generate the full API url based on the defined parameters
        $this->api_full_url = $this->api_protocol."://".$this->api_host.":".$this->api_port."/".$this->api_path."/";

        $this->logger = $Logger;

        $this->requestCounter = 0;
    }

    private function getAuthHeader() {
        return "Authorization: bearer ".$this->permanentAuthToken;
    }

    private function doGet(string $url) {
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header' => $this->getAuthHeader(),
                'ignore_errors' => true
            )
        );

        $context = stream_context_create($opts);

        return HttpCommunicator::doHttpRequest($url, $context);
    }

    private function doApiGet(string $url) {
        $data = array();

        $json = $this->doGet($url);

        if ($json != null && isset($json->data)) {
            $data = $json->data;
        }

        return $data;
    }

    private function doApiPost(string $url, array $post_data) {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n".$this->getAuthHeader()."\r\n",
                'method'  => 'POST',
                'content' => http_build_query($post_data),
                'ignore_errors' => true
            )
        );

        $context  = stream_context_create($options);

        return HttpCommunicator::doHttpRequest($url, $context);
    }

    function getSpaces() : array {
        $url = $this->api_full_url."spaces";

        $spaces = array();

        $api_response = $this->doApiGet($url);

        //Iterate through all spaces existing in json
        foreach ($api_response as $api_space) {
            //Create new space instance with values from API return
            $space = new WrikeSpace($api_space->id, $api_space->title, $api_space->accessType);
            //Add created instance to spaces array
            array_push($spaces, $space);
        }

        return $spaces;
    }

    function getFoldersOfSpace() {

    }

    function getAllFolders() : array {
        $url = $this->api_full_url."folders?deleted=false";

        $folders = array();

        $api_response = $this->doApiGet($url);

        foreach ($api_response as $api_folder) {
            $folder = new WrikeFolder($api_folder->id, $api_folder->title, $api_folder->scope, $api_folder->childIds, $api_folder->parentIds);

            if ($folder->isFolderScope()) {
                array_push($folders, $folder);
            }

        }

        return $folders;
    }

    function getFolderForId(string $folderId) {
        $url = $this->api_full_url."folders/".$folderId;

        $api_response = $this->doApiGet($url);

        foreach ($api_response as $api_folder) {
            $folder = new WrikeFolder($api_folder->id, $api_folder->title, $api_folder->scope, $api_folder->childIds, $api_folder->parentIds);
            if ($folder->isFolderScope()) {
                return $folder;
            }
        }

        return null;
    }

    public function getSubFoldersOfFolderId(string $folderId) {
        $folder = $this->getFolderForId($folderId);
        return $this->getSubFoldersOfFolder($folder);
    }

    function getSubFoldersOfFolder(WrikeFolder $folder) {
        $folders = array();

        if ($folder != null && sizeof($folder->getChildIds()) > 0) {
            $url = $this->api_full_url."folders/";
            foreach ($folder->getChildIds() as $childId) {
                $url = $url.$childId.",";
            }

            $api_response = $this->doApiGet($url);

            foreach ($api_response as $api_folder) {
                $subFolder = new WrikeFolder($api_folder->id, $api_folder->title, $api_folder->scope, $api_folder->childIds, $api_folder->parentIds);

                if ($subFolder->isFolderScope()) {
                    array_push($folders, $subFolder);
                }
            }
        }

        return $folders;
    }

    function getTasksForFolderId(string $folderId) {
        $url = $this->api_full_url."folders/".$folderId."/tasks";

        $tasks = array();

        $api_response = $this->doApiGet($url);

        //Because the API does not return the parent IDs here we have to add it manually
        $parentIds = [$folderId];

        //Iterate though all fetched tasks for this space
        //We do not have supertask IDs here because it is no subtask, just as task of a folder
        foreach ($api_response as $api_task) {
            $task = new WrikeTask($api_task->id, $api_task->title, $api_task->status, null, $parentIds, null);

            array_push($tasks, $task);
        }

        return $tasks;
    }

    function getTasksForSpace(WrikeSpace $space) : array {
        return $this->getTasksForSpaceId($space->getSpaceId());
    }

    function getTasksForSpaceId(string $spaceId) {
        $url = $this->api_full_url."spaces/".$spaceId."/tasks";

        $tasks = array();

        $api_response = $this->doApiGet($url);

        //Iterate though all fetched tasks for this space
        foreach ($api_response as $api_task) {
            $task = new WrikeTask($api_task->id, $api_task->title, $api_task->status, $api_task->subTaskIds, $api_task->parentIds, $api_task->superTaskIds);

            array_push($tasks, $task);
        }

        return $tasks;
    }

    function getSubTasksForTask(WrikeTask $task) {
        $fullTask = $this->getTaskForId($task->getTaskId());
        return $this->getTasksForIds($fullTask->getSubTaskIds());
    }

    function getTasksForIds($taskIds) {
        $tasks = array();

        if ($taskIds != null && sizeof($taskIds) > 0) {
            $url = $this->api_full_url."tasks/";
            foreach ($taskIds as $taskId) {
                $url = $url.$taskId.",";
            }

            $api_response = $this->doApiGet($url);

            foreach ($api_response as $api_task) {
                $task = new WrikeTask($api_task->id, $api_task->title, $api_task->status, $api_task->subTaskIds, $api_task->parentIds, $api_task->superTaskIds);
                array_push($tasks, $task);
            }
        }

        return $tasks;
    }

    function getTaskForId(string $taskId) {
        $url = $this->api_full_url."tasks/".$taskId;

        $task = null;

        $api_response = $this->doApiGet($url);
        //If there is one element in the response, get it as task
        if (sizeof($api_response) == 1) {
            $api_task = $api_response[0];

            $task = new WrikeTask($api_task->id, $api_task->title, $api_task->status, $api_task->subTaskIds, $api_task->parentIds, $api_task->superTaskIds);
        }

        return $task;
    }

    function createCommentForTaskId(string $taskId, string $comment) {
        $url = $this->api_full_url."tasks/".$taskId."/comments";

        $post_data = array(
            "plainText" => true,
            "text" => $comment
        );

        echo "Making new comment for task with ID $taskId and text $comment";

        return $this->doApiPost($url, $post_data);
    }

    function createCommentForFolderId(string $folderId, string $comment) {
        $url = $this->api_full_url."folders/".$folderId."/comments";

        $post_data = array(
            "plainText" => true,
            "text" => $comment
        );

        echo "Making new comment for folder with ID $folderId and text $comment";

        return $this->doApiPost($url, $post_data);
    }
}