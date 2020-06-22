<?php


namespace OCA\WrikeSync\Migration;

use Closure;
use OCA\WrikeSync\Db\ConfigParameter;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version002000Date20200527145400 extends SimpleMigrationStep
{

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('wr_parameters')) {
            $table = $schema->createTable('wr_parameters');

            //Add the ID column that we need for all database entities.
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);

            //Add column to save the key which cannot be null
            $table->addColumn('key', 'string', [
                'notnull' => true,
            ]);

            //Add column to save the value
            $table->addColumn('value', 'text', []);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['key'], 'key_uniq_index');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
        //$query = $this->db->getQueryBuilder();

        $wrikeProtocol = new ConfigParameter();
        $wrikeProtocol->setKey(ConfigParameter::$KEY_WRIKE_API_PROTOCOL);
        $wrikeProtocol->setValue("https");

        $wrikeHost = new ConfigParameter();
        $wrikeHost->setKey(ConfigParameter::$KEY_WRIKE_API_HOST);
        $wrikeHost->setValue("www.wrike.com");

        $wrikePort = new ConfigParameter();
        $wrikePort->setKey(ConfigParameter::$KEY_WRIKE_API_PORT);
        $wrikePort->setValue("443");

        $wrikePath = new ConfigParameter();
        $wrikePath->setKey(ConfigParameter::$KEY_WRIKE_API_PATH);
        $wrikePath->setValue("api/v4");

        //Todo: Insert these default values to database.
    }
}