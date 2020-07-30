<?php
namespace OCA\WrikeSync\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class NodeFolderMappingMapper extends QBMapper
{

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'wr_node_folder_map');
    }

    public function findMappingForNodeId($nodeId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_folder_map')
            ->where(
                $qb->expr()->eq('nc_node_id', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findMappingForFolderId(string $folderId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_folder_map')
            ->where(
                $qb->expr()->eq('wr_folder_id', $qb->createNamedParameter($folderId, IQueryBuilder::PARAM_STR))
            );

        return $this->findEntity($qb);
    }

    /**
     * Do it on the own way. Inserting via DBAL library insert function does not work, so
     * we have to build the insert query manually.
     *
     * @param NodeFolderMapping $mapping
     */
    public function create(NodeFolderMapping $mapping) {
        $qb = $this->db->getQueryBuilder();

        $qb->insert('wr_node_folder_map')
            ->values(array(
                'nc_node_id' => $qb->createNamedParameter($mapping->getNcNodeId(), IQueryBuilder::PARAM_INT),
                'wr_folder_id' => $qb->createNamedParameter($mapping->getWrFolderId(), IQueryBuilder::PARAM_STR),
                'wr_parent_id' => $qb->createNamedParameter($mapping->getWrParentId(), IQueryBuilder::PARAM_STR)
            ));

        return $qb->execute();
    }

    public function find(int $id) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_folder_map')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll($limit=null, $offset=null) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('wr_node_folder_map')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }
}