<?php
namespace OCA\WrikeSync\AppInfo;

use OCA\WrikeSync\Controller\NodeFolderMappingController;
use OCA\WrikeSync\Controller\SynchronizationController;
use OCA\WrikeSync\Controller\WrikeSpaceController;
use OCA\WrikeSync\Cron\SynchronizationTask;
use OCA\WrikeSync\Db\ConfigParameterMapper;
use OCA\WrikeSync\Db\NodeFolderMappingMapper;
use OCA\WrikeSync\Db\NodeTaskMappingMapper;
use OCA\WrikeSync\Db\WrikeFileNotificationMapper;
use OCA\WrikeSync\Service\ConfigParameterService;
use OCA\WrikeSync\Service\FileSystemService;
use OCA\WrikeSync\Service\NodeFolderMappingService;
use OCA\WrikeSync\Service\NodeTaskMappingService;
use OCA\WrikeSync\Service\WrikeFileNotificationService;
use OCA\WrikeSync\Service\WrikeFolderService;
use OCA\WrikeSync\Service\WrikeSpaceService;
use OCA\WrikeSync\Service\WrikeTaskService;
use OCA\WrikeSync\Wrike\WrikeAPIController;
use OCP\AppFramework\App;

class Application extends App
{

    public function __construct(array $urlParams = [])
    {
        parent::__construct('wrikesync', $urlParams);
    }

    public function register() : void {
        $container = $this->getContainer();
        $server = $container->getServer();

        //Register the logger
        $container->registerService('Logger',function($c){
            return $c->query('ServerContainer')->getLogger();
        });

        //Register the FileSystem service which uses the RootStorage!
        $container->registerService("FileSystemService", function($c) {
            return new FileSystemService($c->query("AppName"), $c->query("Logger"), $c->query('ServerContainer')->getRootFolder(), $c->query("ConfigParameterService"), $c->query("UserId"));
        });

        $container->registerService('WrikeAPIController',function($c) {
            return new WrikeAPIController($c->query("ConfigParameterService"), $c->query("Logger"));
        });

        //Register the mappers which are doing the database queries and pass the database connection to them!
        $container->registerService('NodeFolderMappingMapper',function($c) {
            return new NodeFolderMappingMapper($c->query('ServerContainer')->getDatabaseConnection());
        });
        $container->registerService('NodeTaskMappingMapper',function($c) {
            return new NodeTaskMappingMapper($c->query('ServerContainer')->getDatabaseConnection());
        });
        $container->registerService('WrikeFileNotificationMapper',function($c) {
            return new WrikeFileNotificationMapper($c->query('ServerContainer')->getDatabaseConnection());
        });
        $container->registerService('ConfigParameterMapper',function($c) {
            return new ConfigParameterMapper($c->query('ServerContainer')->getDatabaseConnection());
        });

        //Register the service classes which are using the registered mapper classes
        $container->registerService('NodeFolderMappingService',function($c) {
            return new NodeFolderMappingService($c->query("NodeFolderMappingMapper"), $c->query("FileSystemService"));
        });
        $container->registerService('NodeTaskMappingService',function($c) {
            return new NodeTaskMappingService($c->query("NodeTaskMappingMapper"));
        });
        $container->registerService('WrikeFileNotificationService',function($c) {
            return new WrikeFileNotificationService($c->query("WrikeFileNotificationMapper"));
        });
        $container->registerService('ConfigParameterService',function($c) {
            return new ConfigParameterService($c->query("ConfigParameterMapper"), $c->query("UserId"));
        });
        $container->registerService('WrikeSpaceService',function($c) {
            return new WrikeSpaceService($c->query("WrikeAPIController"));
        });
        $container->registerService('WrikeFolderService',function($c) {
            return new WrikeFolderService($c->query("WrikeAPIController"));
        });
        $container->registerService('WrikeTaskService',function($c) {
            return new WrikeTaskService($c->query("WrikeAPIController"));
        });



        $container->registerService('SynchronizationTask',function($c) {
            return new SynchronizationTask(
                $c->getTimeFactory(),
                $c->query("SynchronizationController"),
                $c->getLogger()
            );
        });
    }

}