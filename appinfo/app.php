<?php

use OCA\WrikeSync\AppInfo\Application;

$app = new Application();
$app->register();

$eventDispatcher = \OC::$server->getEventDispatcher();