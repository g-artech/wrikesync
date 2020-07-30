<?php
namespace OCA\WrikeSync\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class NodeFolderMapping extends Entity implements JsonSerializable
{
    protected $ncNodeId;
    protected $wrFolderId;
    protected $wrParentId;
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

    /**
     * @param mixed $wrParentId
     */
    public function setWrParentId($wrParentId): void
    {
        $this->wrParentId = $wrParentId;
    }

    /**
     * @return mixed
     */
    public function getWrParentId()
    {
        return $this->wrParentId;
    }

    public function hasParentId() {
        return $this->wrParentId != null;
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
            "wr_parent_id" => $this->wrParentId,
            "full_path" => $this->fullPath
        ];
    }
}