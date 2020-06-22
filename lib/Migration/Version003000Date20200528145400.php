<?php

namespace OCA\WrikeSync\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version003000Date20200528145400 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('wr_node_folder_map')) {
            $table = $schema->createTable('wr_node_folder_map');

            //Add the ID column that we need for all database entities.
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);

            //Add column to save the node (file/folder) ID of nextcloud (which is an integer)
            $table->addColumn('nc_node_id', 'integer', [
                'notnull' => true,
            ]);

            //Also add column to map the space ID from Wrike to this node ID.
            $table->addColumn('wr_folder_id', 'string', [
                'notnull' => true,
            ]);

            //If a new space is created in Wrike there is no sync initially between Nextcloud filesystem and Wrike.
            //The administrator which manages the plugin has to open the management UI of the plugin to define
            //the root folder (node) of the Nextcloud filesystem which is used as the entry point for the Wrike
            //space. Below this defined folder, there will be created a folder structure based on the task
            //structure in Wrike.
            //We do not have to sync the names of the root folders and space titles because the root folder which is
            //defined by the administrator can have a completely different name as the mapped space of Wrike.
            //We only have to sync the created sub-folders which are mapped to Wrike tasks.

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['nc_node_id', 'wr_folder_id'], 'node_folder_index');
        }

        return $schema;
    }
}