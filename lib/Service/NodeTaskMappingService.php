<?php
namespace OCA\WrikeSync\Service;

use Exception;

use OCA\WrikeSync\Db\NodeTaskMappingMapper;
use OCA\WrikeSync\Db\NodeTaskMapping;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Service class for CRUD handling of NodeTaskMapping entities.
 *
 * Class NodeTaskMappingService
 * @package OCA\WrikeSync\Service
 */
class NodeTaskMappingService
{
    private $mapper;

    public function __construct(NodeTaskMappingMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function findAll(string $userId) {
        return $this->mapper->findAll($userId);
    }

    public function findMappingByNodeId(string $nodeId) {
        try {
            return $this->mapper->findMappingByNodeId($nodeId);
        } catch(Exception $e) {
            return null;
        }
    }

    public function findMappingByTaskId(string $taskId) {
        try {
            return $this->mapper->findMappingByTaskId($taskId);
        } catch(Exception $e) {
            return null;
        }
    }

    public function find(int $id) {
        try {
            return $this->mapper->find($id);
        } catch(Exception $e) {
            return null;
        }
    }

    public function create(string $ncNodeId, string $wrTaskId) {
        $nodeTaskMapping = new NodeTaskMapping();
        $nodeTaskMapping->setNcNodeId($ncNodeId);
        $nodeTaskMapping->setWrTaskId($wrTaskId);

        return $this->mapper->create($nodeTaskMapping);
    }

    public function delete($nodeTaskMapping) {
        try {
            $this->mapper->delete($nodeTaskMapping);
            return $nodeTaskMapping;
        } catch(Exception $e) {
            return null;
        }
    }

    public function deleteById(int $id) {
        try {
            $nodeTaskMapping = $this->mapper->find($id);
            $this->mapper->delete($nodeTaskMapping);
            return $nodeTaskMapping;
        } catch(Exception $e) {
            return null;
        }
    }
}