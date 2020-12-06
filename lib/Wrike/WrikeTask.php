<?php
namespace OCA\WrikeSync\Wrike;

use JsonSerializable;

class WrikeTask implements JsonSerializable
{

    private $taskId;
    private $title;
    private $subTaskIds;
    private $parentIds;
    private $superTaskIds;
    private $status;

    public function __construct($taskId, $title, $status, $subTaskIds, $parentIds, $superTaskIds)
    {
        $this->taskId = $taskId;
        $this->title = str_replace(array('/'), array('_'), $title);
        $this->subTaskIds = $subTaskIds;
        $this->status = $status;
        $this->parentIds = $parentIds;
        $this->superTaskIds = $superTaskIds;
    }

    function getTaskId() {
        return $this->taskId;
    }

    function getTaskTitle() {
        if ($this->isCompleted()) {
            return $this->title."_COMPLETED";
        }
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getSubTaskIds()
    {
        return $this->subTaskIds;
    }

    /**
     * @return mixed
     */
    public function getParentIds()
    {
        return $this->parentIds;
    }

    public function getParentId() {
        if ($this->parentIds == null || sizeof($this->parentIds) == 0) {
            return null;
        } else {
            return $this->parentIds[0];
        }
    }

    /**
     * @return mixed
     */
    public function getSuperTaskIds()
    {
        return $this->superTaskIds;
    }

    public function getSuperTaskId() {
        if ($this->superTaskIds == null || sizeof($this->superTaskIds) == 0) {
            return null;
        } else {
            return $this->superTaskIds[0];
        }
    }

    public function isCompleted() {
        return strcasecmp("Completed", $this->status) == 0;
    }

    public function jsonSerialize()
    {
        return [
            "taskId" => $this->taskId,
            "title" => $this->title,
            "subTaskIds" => $this->subTaskIds,
            "parentIds" => $this->parentIds,
            "parentId" => $this->getParentId(),
            "superTaskIds" => $this->superTaskIds,
            "superTaskId" => $this->getSuperTaskId(),
            "status" => $this->status
        ];
    }
}