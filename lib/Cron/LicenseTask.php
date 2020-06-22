<?php


namespace OCA\WrikeSync\Cron;

use OCA\WrikeSync\AppInfo\AppLogger;
use OCA\WrikeSync\Controller\LicenseController;
use OCA\WrikeSync\Service\FileSystemService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\ILogger;

class LicenseTask extends TimedJob
{

    private $logger;
    private $licenseController;

    public function __construct(ITimeFactory $time, ILogger $Logger, LicenseController $licenseController)
    {
        parent::__construct($time);
        //Do license check every 12 hours
        $this->setInterval((12 * 60 * 60));

        $this->logger = $Logger;
        $this->licenseController = $licenseController;
    }

    public function run($argument) {
        $this->licenseController->checkLicenseAndConfig();
    }

}