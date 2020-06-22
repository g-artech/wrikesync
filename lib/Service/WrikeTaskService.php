<?php

namespace OCA\WrikeSync\Service;

use OCA\WrikeSync\Wrike\WrikeAPIController;

class WrikeTaskService
{

    private $api;

    public function __construct(WrikeAPIController $WrikeAPIController)
    {
        $this->api = $WrikeAPIController;
    }

    public function find($taskId) {
        return $this->api->getTaskForId($taskId);
    }
}