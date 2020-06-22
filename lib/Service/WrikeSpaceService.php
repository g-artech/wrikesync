<?php

namespace OCA\WrikeSync\Service;

use OCA\WrikeSync\Wrike\WrikeAPIController;

class WrikeSpaceService
{

    private $api;

    public function __construct(WrikeAPIController $WrikeAPIController)
    {
        $this->api = $WrikeAPIController;
    }

    public function findAll() {
        return $this->api->getSpaces();
    }
}