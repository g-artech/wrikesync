<?php
namespace OCA\WrikeSync\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class NodeTaskMapping extends Entity implements JsonSerializable
{
    protected $ncNodeId;
    protected $wrTaskId;

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
     * @return mixed
     */
    public function getWrTaskId()
    {
        return $this->wrTaskId;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "nc_node_id" => $this->ncNodeId,
            "wr_task_id" => $this->wrTaskId
        ];
    }
}