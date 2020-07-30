<?php

namespace OCA\WrikeSync\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version004000Date20200715133100 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('wr_node_folder_map')) {
            $table = $schema->getTable('wr_node_folder_map');

            //Add column for wrike parent ID to table with default value null
            $table->addColumn('wr_parent_id', 'string', [
                'notnull' => false,
            ]);
        }

        return $schema;
    }
}