<?php
namespace OCA\WrikeSync\Wrike;

use JsonSerializable;

class WrikeTask implements JsonSerializable
{

    private $taskId;
    private $title;
    private $subTaskIds;
    private $status;

    public function __construct($taskId, $title, $status, $subTaskIds)
    {
        $this->taskId = $taskId;
        $this->title = str_replace(array('/'), array('_'), $title);
        $this->subTaskIds = $subTaskIds;
        $this->status = $status;
    }

    function getTaskId() {
        return $this->taskId;
    }

    function getTaskTitle() {
        if ($this->isCompleted()) {
            return "COMPLETED_".$this->title;
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

    public function isCompleted() {
        return strcasecmp("Completed", $this->status) == 0;
    }

    public function jsonSerialize()
    {
        return [
            "taskId" => $this->taskId,
            "title" => $this->title,
            "subTaskIds" => $this->subTaskIds,
            "status" => $this->status
        ];
    }
}