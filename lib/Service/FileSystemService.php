<?php

namespace OCA\WrikeSync\Service;

use OCA\WrikeSync\AppInfo\AppLogger;
use OCA\WrikeSync\Db\ConfigParameter;
use OCA\WrikeSync\Wrike\NextcloudFolder;
use OCP\Files\IRootFolder;
use OCP\ILogger;
use OCP\Files\Node;
use OCP\Files\Folder;
use OCP\Files\File;
use OCA\WrikeSync\Service\ConfigParameterService;

class FileSystemService
{

    private $rootFolder;
    private $appName;
    private $logger;
    private $parameter;
    private $UserId;

    public function __construct($appName, ILogger $logger, IRootFolder $storage, ConfigParameterService $parameter, $UserId)
    {
        $this->appName = $appName;
        $this->logger = $logger;
        $this->rootFolder = $storage;
        $this->parameter = $parameter;
        $this->UserId = $UserId;
    }

    private function createNextcloudFolderFromFsFolder($fsFolder) {
        if ($fsFolder == null) {
            return null;
        }

        $children = array();

        foreach ($this->getFsSubFoldersOfFsFolder($fsFolder) as $child) {
            $children[$child->getName()] = $child->getId();
        }

        return new NextcloudFolder($fsFolder->getId(), $fsFolder->getPath(), $fsFolder->getName(), $fsFolder->getParent()->getId(), $fsFolder->getMTime(), $children);
    }

    public function getNextcloudSyncRootFolder() {
        $folder = $this->getFsSyncRootFolder();

        return $this->createNextcloudFolderFromFsFolder($folder);
    }

    public function getNextcloudHomeFolderForUser() {
        $relativePath = "/".$this->UserId."/files";
        if ($this->rootFolder->nodeExists($relativePath)) {
            //echo "Node exists";
            return $this->rootFolder->get($relativePath);
        }

        return null;
    }

    public function getNextcloudChildrenOfFolderId($folderId) {
        $folder = $this->getFsFolderById($folderId);

        $children = array();

        foreach ($this->getFsSubFoldersOfFsFolder($folder) as $child) {
            array_push($children, $this->createNextcloudFolderFromFsFolder($child));
        }

        return $children;
    }

    public function getNextcloudFolderOfId($folderId) {
        $folder = $this->getFsFolderById($folderId);

        return $this->createNextcloudFolderFromFsFolder($folder);
    }

    public function getFsSyncRootFolder() {
        $folderId = $this->parameter->findValueForKey(ConfigParameter::$KEY_NEXTCLOUD_FILESYSTEM_ROOT_ID);
        $folder = $this->getFsFolderById($folderId);

        //If the folder could not be found by the defined ID use the home folder of the requesting user as sync root
        if ($folder == null) {
            //echo "Sync root is not defined. Using home directory of user ".$this->UserId;
            $folder = $this->getNextcloudHomeFolderForUser($this->UserId);
        }

        return $folder;
    }

    public function createFsFileInSyncRootFolder($fileName) {
        $syncRoot = $this->getFsSyncRootFolder();
        $syncRoot->newFile($fileName);
    }

    public function createFsFileInFsFolder(Folder $folder, $fileName) {
        $folder->newFile($fileName);
    }

    public function createFsFolderInFsSyncRootFolder($folderName) {
        $root = $this->getFsSyncRootFolder();
        return $root->newFolder($folderName);
    }

    public function createFsFolderInFsFolderId($folderId, $folderName) {
        $folder = $this->getFsFolderById($folderId);
        return $this->createFsFolderInFsFolder($folder, $folderName);
    }

    public function createFsFolderInFsFolder($folder, $folderName) {
        //If the given folder is null just return null
        if ($folder == null) {
            return null;
        }
        
        //If there is already a folder with the new name then return it
        if ($folder->nodeExists($folderName)) {
            return $folder->get($folderName);
        }
        return $folder->newFolder($folderName);
    }

    private function initFileSystem() {
        //Get the defined sync user from the config
        $syncUser = $this->parameter->findValueForKey(ConfigParameter::$KEY_NEXTCLOUD_FILESYSTEM_SYNC_USER);
        //If no sync user is defined use the admin user
        if ($syncUser == null) {
            $syncUser = "admin";
        }

        //We definitely have to init the filesystem before each access!
        //The defined user of the filesystem initialization has to own the sync root folder!
        //So the sync root folder has to be in the defined users home directory.
        //Otherwise nextcloud API will prevent the retrieving of the folder.
        if(\OC\Files\Filesystem::init($syncUser, "/")) {
            //If the filesystem was not initialized before log this message
            //echo "FileSystem initialized for sync user $syncUser and path '/'<br>";
        }
    }

    public function getFsFolderById($folderId) {
        $this->initFileSystem();
        if ($folderId != null && $folderId > 0) {
            $folders = $this->rootFolder->getById($folderId);
            if (sizeof($folders) >= 1) {
                if (sizeof($folders) > 1) {
                    AppLogger::logWarning($this->logger, "Found multiple folders for ID '$folderId'! Just using first one.");
                }
                return $folders[0];
            } else {
                AppLogger::logError($this->logger, "Failed to get folder for specific ID! Folder with ID '$folderId' cannot be found.");
            }
        }
        return null;
    }

    public function getRelativeFsFolderFromSyncRoot($relativePath) {
        $syncRoot = $this->getFsSyncRootFolder();

        if ($syncRoot->nodeExists($relativePath)) {
            return $syncRoot->get($relativePath);
        }

        return null;
    }

    public function getFsSubFoldersOfFsFolder($folder) : array {
        $folders = array();

        //Only go through subfolders if folder is not null!
        if ($folder != null) {
            foreach ($folder->getDirectoryListing() as $node) {
                if ($node instanceof Folder) {
                    array_push($folders, $node);
                }
            }
        }

        return $folders;
    }

    public function getFsFilesOfFsFolder($folder) : array {
        $files = array();

        //Only get files if folder is not null!
        if ($folder != null) {
            foreach ($folder->getDirectoryListing() as $node) {
                if ($node instanceof File) {
                    array_push($files, $node);
                }
            }
        }

        return $files;
    }

    public function renameFsFolderId($folderId, $newName) {
        $folder = $this->getFsFolderById($folderId);
        $this->renameFsFolder($folder, $newName);
    }

    public function renameFsFolder($folder, $newName)  {
        //Dont do anything if folder is null!
        if ($folder == null) {
            return;
        }
        
        $parentFolder = $folder->getParent();
        $newPath = $parentFolder->getPath()."/".$newName;

        AppLogger::logWarning($this->logger, "Renaming folder ".$folder->getName()." to $newPath.");

        $folder->move($newPath);
    }

    public function moveFsFolder($folder, $newParent) {
        if ($folder == null || $newParent == null) {
            return;
        }

        try {
            $newPath = $newParent->getPath()."/".$folder->getName();

            $folder->move($newPath);

            return true;
        } catch (\Exception $e) {
            AppLogger::logError($this->logger, "Move of folder ".$folder->getName()." to $newPath failed: ".$e->getMessage());
        }

        return false;
    }

}