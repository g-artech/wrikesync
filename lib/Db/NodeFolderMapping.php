<?php
namespace OCA\WrikeSync\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class NodeFolderMapping extends Entity implements JsonSerializable
{
    protected $ncNodeId;
    protected $wrFolderId;
    protected $fullPath;

    /**
     * @param mixed $ncNodeId
     */
    public function setNcNodeId($ncNodeId): void
    {
        $this->ncNodeId = $ncNodeId;
    }

    /**
     * @return mixed
     */
    public function getNcNodeId()
    {
        return $this->ncNodeId;
    }

    /**
     * @param mixed $wrFolderId
     */
    public function setWrFolderId($wrFolderId): void
    {
        $this->wrFolderId = $wrFolderId;
    }

    /**
     * @return mixed
     */
    public function getWrFolderId()
    {
        return $this->wrFolderId;
    }

    public function setFullPath($fullPath) {
        $this->fullPath = $fullPath;
    }

    public function getFullPath() {
        return $this->fullPath;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "nc_node_id" => $this->ncNodeId,
            "wr_folder_id" => $this->wrFolderId,
            "full_path" => $this->fullPath
        ];
    }
}