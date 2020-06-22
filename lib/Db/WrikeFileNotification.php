<?php

namespace OCA\WrikeSync\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class WrikeFileNotification extends Entity implements JsonSerializable
{

    private $ncNodeId;
    private $utcTime;

    /**
     * @param int $ncNodeId
     */
    public function setNcNodeId($ncNodeId): void
    {
        $this->ncNodeId = $ncNodeId;
    }

    /**
     * @return int
     */
    public function getNcNodeId(): int
    {
        return $this->ncNodeId;
    }

    /**
     * @param int $utcTime
     */
    public function setUtcTime(int $utcTime): void
    {
        $this->utcTime = $utcTime;
    }

    /**
     * @return int
     */
    public function getUtcTime(): int
    {
        return $this->utcTime;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "nc_node_id" => $this->ncNodeId,
            "utc_time" => $this->utcTime
        ];
    }
}