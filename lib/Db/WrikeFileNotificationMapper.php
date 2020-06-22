<?php

namespace OCA\WrikeSync\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class WrikeFileNotificationMapper extends QBMapper
{
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'wr_file_notifications');
    }

    public function findByNodeId(int $id) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_file_notifications')
            ->where(
                $qb->expr()->eq('nc_node_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll($limit=null, $offset=null) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_file_notifications')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    public function create(WrikeFileNotification $notification) {
        $qb = $this->db->getQueryBuilder();

        $qb->insert('wr_file_notifications')
            ->values(array(
                'nc_node_id' => $qb->createNamedParameter($notification->getNcNodeId(), IQueryBuilder::PARAM_INT),
                'utc_time' => $qb->createNamedParameter($notification->getUtcTime(), IQueryBuilder::PARAM_INT)
            ));

        return $qb->execute();
    }
}