<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\WrikeSync\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        //Routes for mapping administration
        //Get all mappings for nextcloud folder (ID) to wrike folder (ID)
        ['name' => 'node_folder_mapping#index', 'url' => '/mappings', 'verb' => 'GET'],
        //Get a specific mapping by its ID
        ['name' => 'node_folder_mapping#show', 'url' => '/mappings/{id}', 'verb' => 'GET'],

        ['name' => 'node_folder_mapping#forFolderId', 'url' => '/mappings/forFolder/{id}', 'verb' => 'GET'],
        //Create a mapping with the parameters ncNodeId (nextcloud folder ID) and wrFolderId (Wrike folder ID)
        ['name' => 'node_folder_mapping#create', 'url' => '/mappings', 'verb' => 'POST'],
        //Create a mapping with the relative folder path to the sync root folder with parameters ncNodeName and wrFolderId
        ['name' => 'node_folder_mapping#createForName', 'url' => '/mappings/forName', 'verb' => 'POST'],
        //Delete a specific mapping by its ID
        ['name' => 'node_folder_mapping#destroy', 'url' => '/mappings/{id}', 'verb' => 'DELETE'],

        //Routes for spaces administration
        //Get all wrike spaces from Wrike API
        ['name' => 'wrike_space#index', 'url' => '/wrike/spaces', 'verb' => 'GET'],
        ['name' => 'wrike_folder#index', 'url' => '/wrike/folders', 'verb' => 'GET'],
        ['name' => 'wrike_folder#folder', 'url' => '/wrike/folders/{id}', 'verb' => 'GET'],
        ['name' => 'wrike_folder#subfolders', 'url' => '/wrike/folders/{id}/folders', 'verb' => 'GET'],
        ['name' => 'wrike_folder#tasks', 'url' => '/wrike/folders/{id}/tasks', 'verb' => 'GET'],
        ['name' => 'wrike_task#task', 'url' => '/wrike/tasks/{id}', 'verb' => 'GET'],

        //Routes for configuration parameters
        //Get all configuration parameters which are defined
        ['name' => 'config_parameter#index', 'url' => '/config', 'verb' => 'GET'],
        //Get current user
        ['name' => 'config_parameter#currentUser', 'url' => '/config/currentUser', 'verb' => 'GET'],
        //Get a specific configuration parameter by its internal ID
        ['name' => 'config_parameter#show', 'url' => '/config/{id}', 'verb' => 'GET'],
        //Get the raw string value for a specific parameter by its key
        ['name' => 'config_parameter#showByKey', 'url' => '/config/{key}/value', 'verb' => 'GET'],
        //Create a new config parameter with key and value
        ['name' => 'config_parameter#create', 'url' => '/config', 'verb' => 'POST'],
        //Delete a specific config parameter by its internal ID
        ['name' => 'config_parameter#destroy', 'url' => '/config/{id}', 'verb' => 'DELETE'],

        //Enable these routes only if debugging via web is necessary.
        //['name' => 'logging#info', 'url' => '/logging/info', 'verb' => 'POST'],
        //['name' => 'logging#warning', 'url' => '/logging/warning', 'verb' => 'POST'],
        //['name' => 'logging#error', 'url' => '/logging/error', 'verb' => 'POST'],
        //['name' => 'logging#critical', 'url' => '/logging/critical', 'verb' => 'POST'],

        //Enable these routes only for debugging to check if the FileSystemService is working properly!
        //['name' => 'file_system#syncRoot', 'url' => '/nextcloud/folders', 'verb' => 'GET'],

        // TEST-ROUTE for checking if filesystem can be accessed correctly
        ['name' => 'file_system#folder', 'url' => '/nextcloud/folders/{id}', 'verb' => 'GET'],

        //['name' => 'file_system#create', 'url' => '/nextcloud/folders/{id}/create', 'verb' => 'POST'],
        //['name' => 'file_system#rename', 'url' => '/nextcloud/folders/{id}/rename', 'verb' => 'POST'],
        //['name' => 'file_system#children', 'url' => '/nextcloud/folders/{id}/children', 'verb' => 'GET'],

        //Enable this route only for debugging, better use cron.php to check the correct cron behavior!

        // TEST-ROUTE for checking if sync process is working correctly. This also will display some additional log
        ['name' => 'file_system#sync', 'url' => '/nextcloud/sync', 'verb' => 'GET'],

        // TEST-ROUTE for checking if licensing and configuration process is working correctly.
        // This will also provide some logging information about the re-configration process.
        ['name' => 'file_system#license', 'url' => '/nextcloud/license', 'verb' => 'GET'],
    ]
];
