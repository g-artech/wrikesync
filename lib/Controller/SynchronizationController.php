<?php


namespace OCA\WrikeSync\Controller;

use OCP\Files\Node;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IRequest;
use OCP\AppFramework\Controller;

use OCA\WrikeSync\Db\ConfigParameter;
use OCA\WrikeSync\Service\ConfigParameterService;
use OCA\WrikeSync\Service\FileSystemService;
use OCA\WrikeSync\Service\NodeFolderMappingService;
use OCA\WrikeSync\Service\NodeTaskMappingService;
use OCA\WrikeSync\Service\WrikeFileNotificationService;
use OCA\WrikeSync\Wrike\WrikeAPIController;
use OCA\WrikeSync\Wrike\WrikeFolder;
use OCA\WrikeSync\Wrike\WrikeSpace;
use OCA\WrikeSync\Wrike\WrikeTask;
use OCP\ILogger;

class SynchronizationController extends Controller
{

    private $fileSystem;
    private $nodeTaskMap;
    private $nodeFolderMap;
    private $fileNotificator;
    private $parameterService;
    private $logger;

    public function __construct(string $AppName, IRequest $request,
                                FileSystemService $fileSystem,
                                NodeFolderMappingService $nodeSpaceMap,
                                NodeTaskMappingService $nodeTaskMap,
                                WrikeFileNotificationService $fileNotificator,
                                ConfigParameterService $parameterService,
                                ILogger $Logger)
    {
        parent::__construct($AppName, $request);
        $this->fileSystem = $fileSystem;
        $this->nodeFolderMap = $nodeSpaceMap;
        $this->nodeTaskMap = $nodeTaskMap;
        $this->fileNotificator = $fileNotificator;
        $this->parameterService = $parameterService;
        $this->logger = $Logger;
    }

    public function doSync() {
        //Get the the start timestamp of a possible other cronjob which is currently running
        $lastStart = $this->parameterService->findByKey(ConfigParameter::$KEY_CRONJOB_LAST_EXEC_START);
        //If the timestamp is set there is currently a other cronjob running or not exited properly
        if ($lastStart != null) {
            echo "Found last start parameter with value ".$lastStart->getValue()."<br>";

            $currentTime = time();
            $maxTimeout = 10 * 60; //10 minutes
            //If the start timestamp of the running cronjob is older than the timeout then we ran into an error or
            //non-properly exited cronjob because the parameter should be unset at the end of the job.
            if ($currentTime - $lastStart->getValue() > $maxTimeout) {
                echo "Last start parameter is timed out. Deleting parameter to start next job.<br>";
                //If we have a timeout reset the parameter;
                $this->parameterService->delete($lastStart->getId());
            } else {
                echo "Last start parameter has not timed out. Aborting cronjob to avoid parallel jobs.<br>";
                //If we have no timeout exit the job to prevent parallel jobs.
                return;
            }
        }
        //Set the start timestamp to prevent parallel jobs
        $this->parameterService->create(ConfigParameter::$KEY_CRONJOB_LAST_EXEC_START, time());

        try {
            $api = new WrikeAPIController($this->parameterService, $this->logger);

            $spaces = $api->getSpaces();

            foreach ($spaces as $space) {
                if ($space->isPersonalSpace()) {
                    echo "<br>Ignoring space ".$space->getSpaceTitle()." because it is a private space.<br>";

                    continue;
                }

                //First get the folder for this space
                $spaceFolder = $api->getFolderForId($space->getSpaceId());

                echo "<br>--------------------------------<br>Starting sync process for space ".$space->getSpaceTitle()." (ID: ".$space->getSpaceId().")<br>";

                //Check if the folder is existing
                if ($spaceFolder != null) {

                    echo "Using space-folder ".$spaceFolder->getTitle()." (".$spaceFolder->getFolderId().")<br>";

                    //Then get the project folders (subfolders) for the spaces folder
                    $subfolders = $api->getSubFoldersOfFolder($spaceFolder);

                    foreach ($subfolders as $spaceSubFolder) {
                        echo "<br>Found folder ".$spaceSubFolder->getTitle()." (".$spaceSubFolder->getFolderId().") in space-folder ".$spaceFolder->getTitle()."<br>";
                        //Try to find the configured mapping for the given folder
                        $mapping = $this->nodeFolderMap->findMappingByFolderId($spaceSubFolder->getFolderId());

                        //Just do sync logic if the mapping from WrikeSpace to Nextcloud root node (folder) is configured!
                        if ($mapping != null) {

                            //Get the node ID of the root node (folder) which is the entrypoint for the space
                            $nodeId = $mapping->getNcNodeId();

                            echo "Found mapping for folder ".$spaceSubFolder->getTitle()." (".$spaceSubFolder->getFolderId().") to node with ID ".$nodeId."<br>";

                            //Get the node from the filesystem
                            $node = $this->fileSystem->getFsFolderById($nodeId);
                            //Check if the node is (still) existing and only run the sync if it is
                            if ($node != null) {

                                echo "Found mapping for folder ".$spaceSubFolder->getTitle()." (".$spaceSubFolder->getFolderId().") in space-folder ".$spaceFolder->getTitle()." on filesystem for node with ID $nodeId<br>";

                                $this->doSyncForRootFolder($api, $space, $spaceSubFolder, $node);
                            } else {

                                echo "Cannot find mapping for folder ".$spaceSubFolder->getTitle()." (".$spaceSubFolder->getFolderId().") in space-folder ".$spaceFolder->getTitle()." on filesystem for node with ID $nodeId<br>";

                                $this->nodeFolderMap->delete($mapping->getId());
                            }
                        } else {
                            echo "Cannot sync folder ".$spaceSubFolder->getTitle()." (".$spaceSubFolder->getFolderId().") in space-folder ".$spaceFolder->getTitle()." because no mapping was found<br>";
                        }
                    }
                } else {
                    echo "Unable to sync ".$space->getSpaceTitle()." (ID: ".$space->getSpaceId().") because no folder with space ID found.<br>";
                }
            }

            $this->checkForNewFiles($api);
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED DURING CRONJOB EXECUTION: ".$e->getMessage()."<br>";
        }

        //Get the last start parameter to delete it
        $lastStart = $this->parameterService->findByKey(ConfigParameter::$KEY_CRONJOB_LAST_EXEC_START);

        if ($lastStart != null) {
            echo "Deleting set last start parameter to allow following jobs to start.<br>";

            $this->parameterService->delete($lastStart->getId());
        }

        $this->parameterService->updateLastRunForSync();
    }

    public function checkForNewFiles(WrikeAPIController $api) {
        $fsSyncRoot = $this->fileSystem->getFsSyncRootFolder();

        if ($fsSyncRoot == null) {
            echo "ERROR: Cannot check for new files because sync root not found!<br>";
            return;
        }
        $spaceFolders = $this->fileSystem->getFsSubFoldersOfFsFolder($fsSyncRoot);

        foreach ($spaceFolders as $spaceFolder) {
            $this->checkForNewFilesInFolder($api, $spaceFolder);
        }
    }

    private function checkForNewFilesInFolder(WrikeAPIController $api, Folder $currentNode) {
        echo "Checking for new files in folder ".$currentNode->getName()."<br>";

        $files = $this->fileSystem->getFsFilesOfFsFolder($currentNode);

        echo "Found ".sizeof($files)." files in folder ".$currentNode->getName()."<br>";
        foreach ($files as $file) {
            $notification = $this->fileNotificator->findByNodeId($file->getId());

            if ($notification == null) {
                echo "Found new file ".$file->getName()." in folder ".$currentNode->getName()."<br>";

                $mapping = $this->nodeTaskMap->findMappingByNodeId($currentNode->getId());

                if ($mapping != null) {

                    $task = $api->getTaskForId($mapping->getWrTaskId());
                    $created = false;

                    if ($task != null) {
                        echo "Creating comment for task ".$mapping->getWrTaskId()." of file ".$file->getId()."<br>";
                        $api->createCommentForTaskId($mapping->getWrTaskId(), "New file created: ".$file->getName());
                        $created = true;
                    } else {
                        $folder = $api->getFolderForId($mapping->getWrTaskId());

                        if ($folder != null) {
                            echo "Creating comment for folder ".$mapping->getWrTaskId()." of file ".$file->getId()."<br>";
                            $api->createCommentForFolderId($mapping->getWrTaskId(), "New file created: ".$file->getName());
                            $created = true;
                        } else {
                            echo "Cannot create comment for new file because could not find task or folder for ID ".$mapping->getWrTaskId()."<br>";
                        }
                    }

                    //If comment was created then save the notification to the database
                    if ($created) {
                        echo "Comment successfully created for file ".$file->getName()." in folder ".$currentNode->getName().".<br>";

                        $this->fileNotificator->create($file->getId());
                    }
                }
            }
        }

        $subfolders = $this->fileSystem->getFsSubFoldersOfFsFolder($currentNode);

        foreach ($subfolders as $subfolder) {
            $this->checkForNewFilesInFolder($api, $subfolder);
        }
    }

    private function doSyncForRootFolder(WrikeAPIController $api, WrikeSpace $currentSpace, WrikeFolder $currentFolder, Folder $currentNode) {
        //Get the tasks for the space root (root tasks)

        echo "Synchronizing folder ".$currentFolder->getTitle()." of space ".$currentSpace->getSpaceTitle()."<br>";

        $tasks = $api->getTasksForFolderId($currentFolder->getFolderId());

        echo "Starting recursive sync for ".sizeof($tasks)." tasks"."<br>";

        //For each task check if the task has a folder below the node
        foreach ($tasks as $task) {
            //Start the sync for every task in the root level with the root node as base folder
            $this->doSyncForTask($api, $currentSpace, $currentNode, $task);
        }

        //Also go through all subfolders of the current folder and do the same sync again
        $subfolders = $api->getSubFoldersOfFolder($currentFolder);

        echo "Starting recursive sync for ".sizeof($subfolders)." subfolders"."<br>";

        foreach ($subfolders as $subfolder) {
            $this->doSyncForSubFolder($api, $currentSpace, $subfolder, $currentNode);
        }
    }

    private function doSyncForSubFolder(WrikeAPIController $api, WrikeSpace $currentSpace, WrikeFolder $currentFolder, Folder $currentNode) {
        $folderId = $currentFolder->getFolderId();
        $folderTitle = $currentFolder->getTitle();

        //Abuse the node/task mapping for mapping nodes to folders
        $nodeFolderMapping = $this->nodeTaskMap->findMappingByTaskId($folderId);
        $subNodeForFolder = null;

        echo "Synchronizing sub-folder $folderTitle of space ".$currentSpace->getSpaceTitle()."<br>";

        if ($nodeFolderMapping != null) {
            //If the mapping exists, we already have created a folder for it and have to check if the name is still up to date
            $existingNode = $this->fileSystem->getFsFolderById($nodeFolderMapping->getNcNodeId());

            //If the node is existing, set the sub node for the task to it
            if ($existingNode != null) {
                $subNodeForFolder = $existingNode;
            } else {
                //If the mapping exists but the node does not, delete the mapping and go on as if the mapping did never exist.
                $this->nodeTaskMap->delete($nodeFolderMapping);
                $nodeFolderMapping = null;
            }
        }

        //If the mapping does not exist, this is a new task and we have to create the folder (node) for it, as well as the mapping
        if ($nodeFolderMapping == null) {
            echo "No node-folder mapping found. Creating a new one on filesystem and database.<br>";

            //First create the logical folder on nextcloud file system
            $subNodeForFolder = $this->fileSystem->createFsFolderInFsFolder($currentNode, $folderTitle);
            //Then save the created folder name to the mappings table to map it to the nextcloud task
            $nodeFolderMapping = $this->nodeTaskMap->create($subNodeForFolder->getId(), $folderId);
        }

        //Check if the name of the task  has changed, so we have to rename the folder
        if (strcmp($folderTitle, $subNodeForFolder->getName()) !== 0) {
            $this->fileSystem->renameFsFolder($subNodeForFolder, $folderTitle);
        }

        //Also go through all subfolders of the current folder and do the same sync again
        $subfolders = $api->getSubFoldersOfFolder($currentFolder);

        echo "Starting recursive sync for ".sizeof($subfolders)." subfolders"."<br>";

        foreach ($subfolders as $subfolder) {
            $this->doSyncForSubFolder($api, $currentSpace, $subfolder, $subNodeForFolder);
        }


        //Do sync for all tasks of sub-folder
        $tasks = $api->getTasksForFolderId($currentFolder->getFolderId());

        echo "Starting recursive sync for ".sizeof($tasks)." tasks of sub-folder $folderTitle"."<br>";

        //For each task check if the task has a folder below the node
        foreach ($tasks as $task) {
            //Start the sync for every task in the root level with the root node as base folder
            $this->doSyncForTask($api, $currentSpace, $subNodeForFolder, $task);
        }
    }

    private function doSyncForTask(WrikeAPIController $api, WrikeSpace $currentSpace, Folder $currentNode, WrikeTask $currentTask) {
        $taskId = $currentTask->getTaskId();
        $taskTitle = $currentTask->getTaskTitle();

        echo "Synchronizing task $taskTitle of space ".$currentSpace->getSpaceTitle()."<br>";

        $nodeTaskMapping = $this->nodeTaskMap->findMappingByTaskId($taskId);
        $subNodeForTask = null;

        if ($nodeTaskMapping != null) {
            //If the mapping exists, we already have created a folder for it and have to check if the name is still up to date
            $existingNode = $this->fileSystem->getFsFolderById($nodeTaskMapping->getNcNodeId());

            //If the node is existing, set the sub node for the task to it
            if ($existingNode != null) {
                $subNodeForTask = $existingNode;
            } else {
                //If the mapping exists but the node does not, delete the mapping and go on as if the mapping did never exist.
                $this->nodeTaskMap->delete($nodeTaskMapping);
                $nodeTaskMapping = null;
            }
        }

        //If the mapping does not exist, this is a new task and we have to create the folder (node) for it, as well as the mapping
        if ($nodeTaskMapping == null) {
            echo "No node-task mapping found. Creating a new one on filesystem and database.<br>";

            //First create the logical folder on nextcloud file system
            $subNodeForTask = $this->fileSystem->createFsFolderInFsFolder($currentNode, $taskTitle);
            //Then save the created folder name to the mappings table to map it to the nextcloud task
            $nodeTaskMapping = $this->nodeTaskMap->create($subNodeForTask->getId(), $taskId);
        }

        //Check if the name of the task  has changed, so we have to rename the folder
        if (strcmp($taskTitle, $subNodeForTask->getName()) !== 0) {
            $this->fileSystem->renameFsFolder($subNodeForTask, $taskTitle);
        }

        //Get the sub tasks of the current task
        $tasks = $api->getSubTasksForTask($currentTask);

        echo "Starting recursive sync for ".sizeof($tasks)." sub-tasks"."<br>";

        //For each sub task do the same as above until no sub task exists anymore
        foreach ($tasks as $task) {
            $this->doSyncForTask($api, $currentSpace, $subNodeForTask, $task);
        }
    }

    private function scanForNewFiles(WrikeAPIController $api, WrikeSpace $currentSpace, Folder $currentNode, WrikeTask $currentTask) {
        $subNodes = $this->fileSystem->getFsSubFoldersOfFsFolder($currentNode);

        foreach ($subNodes as $subNode) {
            //Only check sub nodes from type File
            if ($subNode instanceof File) {
                $fileName = $subNode->getName();

                //Todo: Check if the file was created until the last 24 hours and check if we have created a comment for that
                //Todo: If no comment was created for the a new file then create one
            }
        }
    }

}