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
use OCP\IURLGenerator;
use OCP\ILogger;

class SynchronizationController extends Controller
{

    private $fileSystem;
    private $nodeTaskMap;
    private $nodeFolderMap;
    private $fileNotificator;
    private $parameterService;
    private $urlGenerator;
    private $logger;

    public function __construct(string $AppName, IRequest $request,
                                FileSystemService $fileSystem,
                                NodeFolderMappingService $nodeSpaceMap,
                                NodeTaskMappingService $nodeTaskMap,
                                WrikeFileNotificationService $fileNotificator,
                                ConfigParameterService $parameterService,
                                IURLGenerator $urlGenerator,
                                ILogger $Logger)
    {
        parent::__construct($AppName, $request);
        $this->fileSystem = $fileSystem;
        $this->nodeFolderMap = $nodeSpaceMap;
        $this->nodeTaskMap = $nodeTaskMap;
        $this->fileNotificator = $fileNotificator;
        $this->parameterService = $parameterService;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $Logger;
    }

    public function doSync() {
        //Get the the start timestamp of a possible other cronjob which is currently running
        $lastStart = $this->parameterService->findByKey(ConfigParameter::$KEY_CRONJOB_LAST_EXEC_START);
        //If the timestamp is set there is currently a other cronjob running or not exited properly
        if ($lastStart != null) {
            echo "Found last start parameter with value ".$lastStart->getValue()."<br>";

            $currentTime = time();
            $maxTimeout = 60 * 60; //60 Minutes
            //If the start timestamp of the running cronjob is older than the timeout then we ran into an error or
            //non-properly exited cronjob because the parameter should be unset at the end of the job.
            if ($currentTime - $lastStart->getValue() > $maxTimeout) {
                echo "Last start parameter is timed out. Deleting parameter to start next job.<br>";
                //If we have a timeout reset the parameter;
                $this->parameterService->delete($lastStart->getId());

                AppLogger::logWarning($this->logger, "Last start parameter ".$lastStart->getValue()." is timed out. Deleting parameter to start next job.");
            } else {
                echo "Last start parameter has not timed out. Aborting cronjob to avoid parallel jobs.<br>";

                AppLogger::logError($this->logger, "Last start parameter ".$lastStart->getValue()." has not timed out. Aborting cronjob to avoid parallel jobs.");

                //If we have no timeout exit the job to prevent parallel jobs.
                return;
            }
        }
        //Set the start timestamp to prevent parallel jobs
        $this->parameterService->create(ConfigParameter::$KEY_CRONJOB_LAST_EXEC_START, time());

        AppLogger::logInfo($this->logger, "Starting new synchronization with WrikeSync.");

        try {
            $api = new WrikeAPIController($this->parameterService, $this->logger);

            $spaces = $api->getSpaces();

            foreach ($spaces as $space) {
                if ($space->isPersonalSpace()) {
                    echo "<br>Ignoring space ".$space->getSpaceTitle()." because it is a private space.<br>";

                    AppLogger::logWarning($this->logger, "Ignoring space ".$space->getSpaceTitle()." because it is a private space.");

                    continue;
                }

                //First get the folder for this space
                $spaceFolder = $api->getFolderForId($space->getSpaceId());

                echo "<br>--------------------------------<br>Starting sync process for space ".$space->getSpaceTitle()." (ID: ".$space->getSpaceId().")<br>";

                AppLogger::logInfo($this->logger, "Starting sync process for space ".$space->getSpaceTitle()." (ID: ".$space->getSpaceId().")");

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

                            AppLogger::logWarning($this->logger, "Cannot sync folder ".$spaceSubFolder->getTitle()." (".$spaceSubFolder->getFolderId().") in space-folder ".$spaceFolder->getTitle()." because no mapping was found");
                        }
                    }
                } else {
                    echo "Unable to sync ".$space->getSpaceTitle()." (ID: ".$space->getSpaceId().") because no folder with space ID found.<br>";

                    AppLogger::logError($this->logger, "Unable to sync ".$space->getSpaceTitle()." (ID: ".$space->getSpaceId().") because no folder with space ID found.");
                }
            }

            $this->checkForNewFiles($api);
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED DURING CRONJOB EXECUTION: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED DURING CRONJOB EXECUTION: ".$e->getMessage());
        }

        //Get the last start parameter to delete it
        $lastStart = $this->parameterService->findByKey(ConfigParameter::$KEY_CRONJOB_LAST_EXEC_START);

        if ($lastStart != null) {
            echo "Deleting set last start parameter to allow following jobs to start.<br>";

            $this->parameterService->delete($lastStart->getId());
        }

        $this->parameterService->updateLastRunForSync();

        AppLogger::logInfo($this->logger, "Finished synchronization with WrikeSync.");
    }

    public function checkForNewFiles(WrikeAPIController $api) {
        try {
            $fsSyncRoot = $this->fileSystem->getFsSyncRootFolder();
    
            if ($fsSyncRoot == null) {
                echo "ERROR: Cannot check for new files because sync root not found!<br>";
                return;
            }
            $spaceFolders = $this->fileSystem->getFsSubFoldersOfFsFolder($fsSyncRoot);
    
            foreach ($spaceFolders as $spaceFolder) {
                $this->checkForNewFilesInFolder($api, $spaceFolder);
            }
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED WHILE GENERALLY CHECKING FOR NEW FILES: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED WHILE GENERALLY CHECKING FOR NEW FILES: ".$e->getMessage());
        }
    }

    private function checkForNewFilesInFolder(WrikeAPIController $api, Folder $currentNode) {
        echo "Checking for new files in folder ".$currentNode->getName()."<br>";

        try {
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
    
                        //Generate URL to "files" app with parameter of the current nodes ID
                        $baseUrl = $this->parameterService->findValueForKey(ConfigParameter::$KEY_NEXTCLOUD_BASE_URL);
    
                        if ($baseUrl != null) {
                            $linkToFile = $baseUrl."/apps/files/?fileid=".$currentNode->getId();
                        } else {
                            $linkToFile = "#";
                        }
    
                        if ($task != null) {
                            echo "Creating comment for task ".$mapping->getWrTaskId()." of file ".$file->getId()." with link ".$linkToFile."<br>";
    
                            $api->createCommentForTaskId($mapping->getWrTaskId(), "New file created: <a href=\"".$linkToFile."\">".$file->getName()."</a>");
                            $created = true;
                        } else {
                            $folder = $api->getFolderForId($mapping->getWrTaskId());
    
                            if ($folder != null) {
                                echo "Creating comment for folder ".$mapping->getWrTaskId()." of file ".$file->getId()." with link ".$linkToFile."<br>";
                                $api->createCommentForFolderId($mapping->getWrTaskId(), "New file created: <a href=\"".$linkToFile."\">".$file->getName()."</a>");
                                $created = true;
                            } else {
                                echo "Cannot create comment for new file because could not find task or folder for ID ".$mapping->getWrTaskId()."<br>";
                            }
                        }
    
                        //If comment was created then save the notification to the database
                        if ($created) {
                            echo "Comment successfully created for file ".$file->getName()." in folder ".$currentNode->getName().".<br>";
    
                            $this->fileNotificator->create($file->getId());
                        } else {
                            echo "No comment was created! Please check the log above. Maybe the API call was malicious.<br>";
                        }
                    } else {
                        echo "Cannot find any mapping for node of file. No comment is being created. Please check if nextcloud node is mapped to a Wrike folder.<br>";
                    }
                } else {
                    echo "File ".$file->getName()." was already commented.<br>";
                }
            }
    
            $subfolders = $this->fileSystem->getFsSubFoldersOfFsFolder($currentNode);
    
            foreach ($subfolders as $subfolder) {
                $this->checkForNewFilesInFolder($api, $subfolder);
            }
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED WHILE CHECKING FOR NEW FILES IN FOLDER: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED WHILE CHECKING FOR NEW FILES IN FOLDER: ".$e->getMessage());
        }
    }

    private function doSyncForRootFolder(WrikeAPIController $api, WrikeSpace $currentSpace, WrikeFolder $currentFolder, Folder $currentNode) {
        try {
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
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED DURING SYNC OF ROOT FOLDER: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED DURING SYNC OF ROOT FOLDER: ".$e->getMessage());
        }
    }

    private function doSyncForSubFolder(WrikeAPIController $api, WrikeSpace $currentSpace, WrikeFolder $currentFolder, Folder $currentNode) {
        try {
            $folderId = $currentFolder->getFolderId();
            $folderTitle = $currentFolder->getTitle();
            $parentId = $currentFolder->getParentId();
    
            //Abuse the node/task mapping for mapping nodes to folders
            $nodeFolderMapping = $this->nodeTaskMap->findMappingByTaskId($folderId);
            $subNodeForFolder = null;
    
            echo "<br>-<br>";
    
            echo "Synchronizing sub-folder $folderTitle with ID '$folderId' and parent ID '$parentId' of space ".$currentSpace->getSpaceTitle()."<br>";
    
            if ($nodeFolderMapping != null) {
                echo "Found existing mapping for folder. Checking if node of mapping still exists.<br>";
                //If the mapping exists, we already have created a folder for it and have to check if the name is still up to date
                $existingNode = $this->fileSystem->getFsFolderById($nodeFolderMapping->getNcNodeId());
    
                //If the node is existing, set the sub node for the task to it
                if ($existingNode != null) {
                    echo "Node of existing mapping still exists. Using this node for synchronization.<br>";
                    $subNodeForFolder = $existingNode;
                } else {
                    echo "The node of the mapping does not exist anymore. Deleting this mapping.<br>";
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
                $this->nodeTaskMap->create($subNodeForFolder->getId(), $folderId, $parentId);
                $nodeFolderMapping = $this->nodeTaskMap->findMappingByTaskId($folderId);
            }
    
            //Check if the name of the task  has changed, so we have to rename the folder
            if (strcmp($folderTitle, $subNodeForFolder->getName()) !== 0) {
                $this->fileSystem->renameFsFolder($subNodeForFolder, $folderTitle);
            }
    
            if ($nodeFolderMapping->getWrParentId() == null) {
                echo "Found old mapping with not set parent ID. Recreating mapping with parent ID.<br>";
                $this->nodeTaskMap->delete($nodeFolderMapping);
                //Try to get the current parent Wrike ID via the mapping of the current nextcloud parent node
                //If no mapping for the current parent node is found use the parent ID fetched from the Wrike API
                $this->nodeTaskMap->create($subNodeForFolder->getId(), $folderId, $this->getParentWrikeIdForParentNode($currentNode, $parentId));
                $nodeFolderMapping = $this->nodeTaskMap->findMappingByTaskId($folderId);
            }
    
            $mappedParentWrikeId = $nodeFolderMapping->getWrParentId();
    
            //Check if a mapping of the parent node exists (which contains the task)
            if ($mappedParentWrikeId != null) {
    
                echo "Found mapping for parent node $mappedParentWrikeId with node ID ".$currentNode->getId()."<br>";
    
                if (strcmp($mappedParentWrikeId, $parentId) !== 0) {
                    echo "Parent IDs have changed. Current new parent is $parentId and mapped parent is $mappedParentWrikeId.<br>";
                    //Try to find a node task mapping for the new parent to get his node ID
                    $newParentMapping = $this->nodeTaskMap->findMappingByTaskId($parentId);
                    if ($newParentMapping == null) {
                        //If not found try to find the new parent via the manual defined mappings
                        $newParentMapping = $this->nodeFolderMap->findMappingByFolderId($parentId);
                    }
    
                    if ($newParentMapping != null) {
                        echo "Found mapping for new parent ID $parentId to get node ID for move.<br>";
    
                        $newParentNode = $this->fileSystem->getFsFolderById($newParentMapping->getNcNodeId());
    
                        if ($newParentNode != null) {
                            echo "Found new parent node: ".$newParentNode->getName().". Moving folder to it and deleting old mapping.<br>";
                            if($this->fileSystem->moveFsFolder($subNodeForFolder, $newParentNode)) {
                                echo "Successfully moved folder to new parent node.<br>";
                                $this->nodeTaskMap->delete($nodeFolderMapping);
                                echo "Creating new mapping for moved node with new parent.<br>";
                                $this->nodeTaskMap->create($subNodeForFolder->getId(), $folderId, $parentId);
                            } else {
                                echo "FAILED to move folder to new parent node.<br>";
                            }
                        } else {
                            echo "Cannot find node for new parent ID ".$newParentMapping->getNcNodeId()."<br>";
                        }
                    } else {
                        echo "Cannot find mapping for new parent ID $parentId to move folder to it.<br>";
                    }
                } else {
                    echo "Parent IDs of current ($mappedParentWrikeId) and mapped structure ($parentId) are equal. No move required.<br>";
                }
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
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED DURING SYNC OF SUB-FOLDER: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED DURING SYNC OF SUB-FOLDER: ".$e->getMessage());
        }
    }

    private function getParentWrikeIdForParentNode(Folder $parentNode, $defaultId) {
        echo "Trying to get current Wrike parent ID mapped to the current nextcloud parent node ".$parentNode->getId()."<br>";
        //For default set the parent ID to the default parent ID
        $parentWrikeId = $defaultId;
        try {
            //First try to get the parents wrike ID via the node task mappings
            $taskMapping = $this->nodeTaskMap->findMappingByNodeId($parentNode->getId());
            if ($taskMapping == null) {
                //If no task mapping is found, try to find a node folder mapping
                $folderMapping = $this->nodeFolderMap->findMappingByNodeId($parentNode->getId());
                if ($folderMapping == null) {
                    echo "Cannot find parent Wrike ID via parent node ".$parentNode->getId()." for current task. Using parent ID of Wrike API instead: $defaultId<br>";
                } else {
                    $parentWrikeId = $folderMapping->getWrFolderId();
                    echo "Found current parent Wrike ID via nextcloud parent node over folder mapping: $parentWrikeId<br>";
                }
            } else {
                $parentWrikeId = $taskMapping->getWrTaskId();
                echo "Found current parent Wrike ID via nextcloud parent node over task mapping: $parentWrikeId<br>";
            }
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED WHILE FETCHING WRIKE ID OF PARENT NODE: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED WHILE FETCHING WRIKE ID OF PARENT NODE: ".$e->getMessage());
        }

        return $parentWrikeId;
    }

    private function doSyncForTask(WrikeAPIController $api, WrikeSpace $currentSpace, Folder $currentNode, WrikeTask $currentTask) {
        try {
            $taskId = $currentTask->getTaskId();
            $taskTitle = $currentTask->getTaskTitle();
            $parentId = null;
    
            echo "<br>-<br>";
    
            if ($currentTask->getSuperTaskId() != null) {
                echo "Using current tasks super task ID to identify parent because task is a subtask.<br>";
                $parentId = $currentTask->getSuperTaskId();
            } else {
                echo "Using tasks native parent ID to identify parent.<br>";
                $parentId = $currentTask->getParentId();
            }
    
            echo "Synchronizing task '$taskTitle' with ID '$taskId' and parent ID '$parentId' of space ".$currentSpace->getSpaceTitle()."<br>";
    
            $nodeTaskMapping = $this->nodeTaskMap->findMappingByTaskId($taskId);
            $subNodeForTask = null;
    
            if ($nodeTaskMapping != null) {
                echo "An existing mapping was found for this task. Trying to get the node of this mapping.<br>";
                //If the mapping exists, we already have created a folder for it and have to check if the name is still up to date
                $existingNode = $this->fileSystem->getFsFolderById($nodeTaskMapping->getNcNodeId());
    
                //If the node is existing, set the sub node for the task to it
                if ($existingNode != null) {
                    echo "Node of the mapping is existing. Using this for synchronization.<br>";
                    $subNodeForTask = $existingNode;
                } else {
                    echo "The node of the mapping does not exist. Removing mapping!<br>";
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
                $this->nodeTaskMap->create($subNodeForTask->getId(), $taskId, $parentId);
                $nodeTaskMapping = $this->nodeTaskMap->findMappingByTaskId($taskId);
            }
    
            //Check if the name of the task  has changed, so we have to rename the folder
            if (strcmp($taskTitle, $subNodeForTask->getName()) !== 0) {
                $this->fileSystem->renameFsFolder($subNodeForTask, $taskTitle);
            }
    
            if ($nodeTaskMapping->getWrParentId() == null) {
                echo "Found old mapping with not set parent ID. Recreating mapping with parent ID.<br>";
                $this->nodeTaskMap->delete($nodeTaskMapping);
                //Create a new mapping with the current parent ID
                //Try to get the current parent Wrike ID via the mapping of the current nextcloud parent node
                //If no mapping for the current parent node is found use the parent ID fetched from the Wrike API
                $this->nodeTaskMap->create($subNodeForTask->getId(), $taskId, $this->getParentWrikeIdForParentNode($currentNode, $parentId));
                $nodeTaskMapping = $this->nodeTaskMap->findMappingByTaskId($taskId);
            }
    
            //Get the mapping for the current node which is the parent of the task in the nextcloud filesystem. With
            //this we have the Wrike ID of the parent of the task. After this check if the parent ID of the Wrike Task
            //is still the same as the parent ID of the mapping for this node which contains the current task as child.
            //If not, try to find an existing mapping for the new parent ID and move the sub node of the task to the node
            //of the new parent and delete the old mapping. The next sync get the moved task from the API and will not
            //find any mapping. It will create a folder for the task which is already there (because it was moved). This
            //folder will be used then for creation of a new mapping and the sync will go on.
            //Check if there is a node task mapping for the parent to get the Wrike ID from
            $mappedParentWrikeId = $nodeTaskMapping->getWrParentId();
    
            //Check if a mapping of the parent node exists (which contains the task)
            if ($mappedParentWrikeId != null) {
    
                echo "Using mapping for task with task ID '".$nodeTaskMapping->getWrTaskId()."' and parent ID '$mappedParentWrikeId' and node ID ".$nodeTaskMapping->getNcNodeId().".<br>";
    
                if (strcmp($mappedParentWrikeId, $parentId) !== 0) {
                    echo "Parent IDs have changed. Current new parent is $parentId and mapped parent is $mappedParentWrikeId.<br>";
    
                    //Try to find a node task mapping for the new parent to get his node ID
                    $newParentMapping = $this->nodeTaskMap->findMappingByTaskId($parentId);
                    if ($newParentMapping == null) {
                        //If not found try to find the new parent via the manual defined mappings
                        $newParentMapping = $this->nodeFolderMap->findMappingByFolderId($parentId);
                    }
    
                    if ($newParentMapping != null) {
                        echo "Found mapping for new parent ID $parentId to get node ID for move.<br>";
    
                        $newParentNode = $this->fileSystem->getFsFolderById($newParentMapping->getNcNodeId());
    
                        if ($newParentNode != null) {
                            echo "Found new parent node: ".$newParentNode->getName().". Moving folder to it and deleting old mapping.<br>";
                            if($this->fileSystem->moveFsFolder($subNodeForTask, $newParentNode)) {
                                $this->nodeTaskMap->delete($nodeTaskMapping);
                                echo "Folder was successfully moved to new parent node.<br>";
                                echo "Creating new mapping for moved node with new parent.<br>";
                                $this->nodeTaskMap->create($subNodeForTask->getId(), $taskId, $parentId);
                            } else {
                                echo "FAILED to move folder to new parent node.<br>";
                            }
                        } else {
                            echo "Cannot find node for new parent ID ".$newParentMapping->getNcNodeId()."<br>";
                        }
                    } else {
                        echo "Cannot find mapping for new parent ID $parentId to move folder to it.<br>";
                    }
                } else {
                    echo "Parent IDs of current ($mappedParentWrikeId) and mapped structure ($parentId) are equal. No move required.<br>";
                }
            }
    
            //Get the sub tasks of the current task
            $tasks = $api->getSubTasksForTask($currentTask);
    
            echo "Starting recursive sync for ".sizeof($tasks)." sub-tasks"."<br>";
    
            //For each sub task do the same as above until no sub task exists anymore
            foreach ($tasks as $task) {
                $this->doSyncForTask($api, $currentSpace, $subNodeForTask, $task);
            }
        } catch(\Exception $e) {
            echo "AN ERROR OCCURED DURING SYNC OF TASK: ".$e->getMessage()."<br>";

            AppLogger::logCritical($this->logger, "AN ERROR OCCURED DURING SYNC OF TASK: ".$e->getMessage());
        }
    }

}