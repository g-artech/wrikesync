<?php
namespace OCA\WrikeSync\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class NodeTaskMapping extends Entity implements JsonSerializable
{
    protected $ncNodeId;
    protected $wrTaskId;
    protected $wrParentId;

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
     * @param mixed $wrTaskId
     */
    public function setWrTaskId($wrTaskId): void
    {
        $this->wrTaskId = $wrTaskId;
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
    public function getWrTaskId()
    {
        return $this->wrTaskId;
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

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "nc_node_id" => $this->ncNodeId,
            "wr_task_id" => $this->wrTaskId,
            "wr_parent_id" => $this->wrParentId
        ];
    }
}