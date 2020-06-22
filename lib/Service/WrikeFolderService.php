<?php

namespace OCA\WrikeSync\Service;

use OCA\WrikeSync\Wrike\WrikeAPIController;

class WrikeFolderService
{

    private $api;

    public function __construct(WrikeAPIController $WrikeAPIController)
    {
        $this->api = $WrikeAPIController;
    }

    public function findAll() {
        return $this->api->getAllFolders();
    }

    public function find($folderId) {
        return $this->api->getFolderForId($folderId);
    }

    public function getSubFoldersOfFolderId($id) {
        return $this->api->getSubFoldersOfFolderId($id);
    }

    public function getTasksOfFolderId($id) {
        return $this->api->getTasksForFolderId($id);
    }
}