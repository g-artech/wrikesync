<?php

namespace OCA\WrikeSync\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000003Date20200525160601 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('wr_file_notifications')) {
            $table = $schema->createTable('wr_file_notifications');

            //Add the ID column that we need for all database entities.
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);

            //Add column to save the node (file) ID of nextcloud (which is an integer)
            $table->addColumn('nc_node_id', 'integer', [
                'notnull' => true,
            ]);
            //Add column to save the date of commenting to allow deletion of entries older than two days
            $table->addColumn('utc_time', 'integer', [
                'notnull' => true
            ]);

            //If a file gets created in nextcloud we have to create a comment in Wrike on the relating task.
            //To save the information about which file a comment was already created, we are saving the information
            //about the created comment in this table. We should only create comments for files which are
            //created but not saved as commented in this table. We should restrict this checkup for only all
            //files which are not older that 24 hours. So we can cleanup this table at every run by deleting all
            //entries which are older that two days.

            $table->setPrimaryKey(['id']);
        }

        return $schema;
    }
}