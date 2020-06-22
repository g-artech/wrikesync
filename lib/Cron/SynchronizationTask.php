<?php

namespace OCA\WrikeSync\Cron;

use OCA\WrikeSync\AppInfo\AppLogger;
use OCA\WrikeSync\Controller\SynchronizationController;
use OCA\WrikeSync\Db\ConfigParameter;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Node;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\ILogger;

class SynchronizationTask extends TimedJob
{

    private $synchronizationController;
    private $logger;

    public function __construct(ITimeFactory $time,
                                SynchronizationController $synchronizationController,
                                ILogger $Logger)
    {
        parent::__construct($time);

        $this->synchronizationController = $synchronizationController;
        $this->logger = $Logger;

        $this->setInterval(5);
    }

    /**
     * Function which is executed on every cron execution.
     *
     * @param $arguments array which contains the cron arguments.
     */
    public function run($arguments) {
        //We do not need any arguments, so just call the function.
        AppLogger::logError($this->logger,"Starting cron job for synchronization...");
        $this->doSync();
        AppLogger::logError($this->logger,"Synchronization cron job ended.");
    }

    public function doSync() {
        $this->synchronizationController->doSync();
    }

}