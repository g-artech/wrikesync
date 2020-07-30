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
    private $nodeTaskService;

    public function __construct(NodeFolderMappingMapper $mapper, FileSystemService $fileSystemService, NodeTaskMappingService $nodeTaskService)
    {
        $this->mapper = $mapper;
        $this->fileSystemService = $fileSystemService;
        $this->nodeTaskService = $nodeTaskService;
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

    public function findMappingByNodeId($nodeId) {
        try {
            return $this->getMappingWithFullPath($this->mapper->findMappingForNodeId($nodeId));
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

    public function create($ncNodeId, $wrSpaceId, $wrParentId) {
        $nodeFolder = new NodeFolderMapping();
        $nodeFolder->setNcNodeId($ncNodeId);
        $nodeFolder->setWrFolderId($wrSpaceId);
        $nodeFolder->setWrParentId($wrParentId);

        return $this->getMappingWithFullPath($this->mapper->create($nodeFolder));
    }

    public function createForName($ncNodeName, $wrSpaceId, $wrParentId) {
        $folder = $this->fileSystemService->getRelativeFsFolderFromSyncRoot($ncNodeName);

        if ($folder != null) {
            $nodeFolder = new NodeFolderMapping();
            $nodeFolder->setNcNodeId($folder->getId());
            $nodeFolder->setWrFolderId($wrSpaceId);
            $nodeFolder->setWrParentId($wrParentId);

            return $this->getMappingWithFullPath($this->mapper->create($nodeFolder));
        }
        return null;
    }

    public function delete(int $id) {
        try {
            $nodeFolder = $this->mapper->find($id);

            //If mapping is found recursively delete all node task mappings below
            if ($nodeFolder != null) {
                //Get the start node of the mapping
                $node = $this->fileSystemService->getFsFolderById($nodeFolder->getNcNodeId());
                //If the start node still exists then recursively delete the mappings below
                if ($node != null) {
                    $subNodes = $this->fileSystemService->getFsSubFoldersOfFsFolder($node);
                    //Start the recursion of the delete process
                    foreach ($subNodes as $subNode) {
                        $this->deleteMappingForSubNode($subNode);
                    }
                }
                //Finally delete the node folder mapping
                $this->mapper->delete($nodeFolder);
            }

            return $nodeFolder;
        } catch(Exception $e) {
            return null;
        }
    }

    private function deleteMappingForSubNode($subNode) {
        //Try to get the node-task mapping by the ID of the nextcloud node
        $mapping = $this->nodeTaskService->findMappingByNodeId($subNode->getId());
        //If any mapping exists delete it
        if ($mapping != null && $subNode != null) {
            //Delete the mapping
            $this->nodeTaskService->delete($mapping);
            //Get sub nodes of the current node
            $subNodes = $this->fileSystemService->getFsSubFoldersOfFsFolder($subNode);
            //Recursively delete all sub nodes mappings
            foreach ($subNodes as $subNode) {
                $this->deleteMappingForSubNode($subNode);
            }
        }
    }
}