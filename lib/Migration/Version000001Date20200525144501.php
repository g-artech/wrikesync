<?php

namespace OCA\WrikeSync\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000001Date20200525144501 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('wr_node_task_map')) {
            $table = $schema->createTable('wr_node_task_map');
            //Add the ID column that we need for all database entities.
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);

            //Add column to save the node (file/folder) ID of nextcloud (which is an integer)
            $table->addColumn('nc_node_id', 'integer', [
                'notnull' => true,
            ]);

            //Also add column to map the task ID from Wrike to this node ID.
            $table->addColumn('wr_task_id', 'string', [
                'notnull' => true,
            ]);

            //We do not need further information in this table because when iterating through the
            //tasks returned by the Wrike API, we can do a lookup in this table if any mapping is existing.
            //If so, we can check if the folder (node) name in nextcloud does still match the tasks name and
            //can rename the folder if there is any difference. If no mapping exists, we should create the
            //folder (node) in nextcloud and save the mapping in this table.
            //We do not have to check if the folder has been moved because this could not happen. Users are
            //not able to move sub-tasks to other tasks in Wrike.

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['nc_node_id', 'wr_task_id'], 'node_task_index');
        }

        return $schema;
    }
}