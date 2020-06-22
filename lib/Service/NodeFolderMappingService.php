<?php
namespace OCA\WrikeSync\Service;

use Exception;

use OCA\WrikeSync\Db\NodeFolderMapping;
use OCA\WrikeSync\Db\NodeFolderMappingMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Service class for CRUD handling of NodeSpaceMapping entities.
 *
 * Class NodeSpaceMappingService
 * @package OCA\WrikeSync\Service
 */
class NodeFolderMappingService
{
    private $mapper;
    private $fileSystemService;

    public function __construct(NodeFolderMappingMapper $mapper, FileSystemService $fileSystemService)
    {
        $this->mapper = $mapper;
        $this->fileSystemService = $fileSystemService;
    }

    public function findAll() {
        $fullMappings = array();

        foreach($this->mapper->findAll() as $mapping) {
            array_push($fullMappings, $this->getMappingWithFullPath($mapping));
        }

        return $fullMappings;
    }

    private function getMappingWithFullPath($mapping) {
        if ($mapping != null) {
            $nextcloudFolder = $this->fileSystemService->getNextcloudFolderOfId($mapping->getNcNodeId());

            if ($nextcloudFolder != null) {
                $mapping->setFullPath($nextcloudFolder->getFullPath());
            }
            return $mapping;
        }

        return null;
    }

    public function findMappingByFolderId(string $folderId) {
        try {
            return $this->getMappingWithFullPath($this->mapper->findMappingForFolderId($folderId));
        } catch(Exception $e) {
            return null;
        }
    }

    public function find(int $id) {
        try {
            return $this->getMappingWithFullPath($this->mapper->find($id));
        } catch(Exception $e) {
            return null;
        }
    }

    public function create($ncNodeId, $wrSpaceId) {
        $nodeFolder = new NodeFolderMapping();
        $nodeFolder->setNcNodeId($ncNodeId);
        $nodeFolder->setWrFolderId($wrSpaceId);

        return $this->getMappingWithFullPath($this->mapper->create($nodeFolder));
    }

    public function createForName($ncNodeName, $wrSpaceId) {
        $folder = $this->fileSystemService->getRelativeFsFolderFromSyncRoot($ncNodeName);

        if ($folder != null) {
            $nodeFolder = new NodeFolderMapping();
            $nodeFolder->setNcNodeId($folder->getId());
            $nodeFolder->setWrFolderId($wrSpaceId);

            return $this->getMappingWithFullPath($this->mapper->create($nodeFolder));
        }
        return null;
    }

    public function delete(int $id) {
        try {
            $nodeFolder = $this->mapper->find($id);
            $this->mapper->delete($nodeFolder);
            return $nodeFolder;
        } catch(Exception $e) {
            return null;
        }
    }
}