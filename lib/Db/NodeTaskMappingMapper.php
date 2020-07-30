<?php

namespace OCA\WrikeSync\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class NodeTaskMappingMapper extends QBMapper
{
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'wr_node_task_map');
    }

    public function create(NodeTaskMapping $mapping) {
        $qb = $this->db->getQueryBuilder();

        $qb->insert('wr_node_task_map')
            ->values(array(
                'nc_node_id' => $qb->createNamedParameter($mapping->getNcNodeId(), IQueryBuilder::PARAM_INT),
                'wr_task_id' => $qb->createNamedParameter($mapping->getWrTaskId(), IQueryBuilder::PARAM_STR),
                'wr_parent_id' => $qb->createNamedParameter($mapping->getWrParentId(), IQueryBuilder::PARAM_STR)
            ));

        return $qb->execute();
    }

    public function findMappingByNodeId(string $nodeId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_task_map')
            ->where(
                $qb->expr()->eq('nc_node_id', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findMappingByTaskId(string $taskId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_task_map')
            ->where(
                $qb->expr()->eq('wr_task_id', $qb->createNamedParameter($taskId, IQueryBuilder::PARAM_STR))
            );

        return $this->findEntity($qb);
    }

    public function find(int $id) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_task_map')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll($limit=null, $offset=null) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_task_map')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    public function clear() {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('wr_node_task_map');

        return $qb->execute();
    }
}