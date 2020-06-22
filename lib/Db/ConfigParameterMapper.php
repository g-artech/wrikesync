<?php


namespace OCA\WrikeSync\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class ConfigParameterMapper extends QBMapper
{

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'wr_parameters');
    }

    public function findValueForKey(string $key) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_parameters')
            ->where(
                $qb->expr()->eq('key', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR))
            );

        return $this->findEntity($qb);
    }

    /**
     * Do it on the own way. Inserting via DBAL library insert function does not work, so
     * we have to build the insert query manually.
     *
     * @param ConfigParameter $parameter
     */
    public function create(ConfigParameter $parameter) {
        $qb = $this->db->getQueryBuilder();

        $qb->insert('wr_parameters')
            ->values(array(
                'key' => $qb->createNamedParameter($parameter->getKey(), IQueryBuilder::PARAM_STR),
                'value' => $qb->createNamedParameter($parameter->getValue(), IQueryBuilder::PARAM_STR)
            ));

        return $qb->execute();
    }

    public function find(int $id) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_parameters')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll($limit=null, $offset=null) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_parameters')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }
}