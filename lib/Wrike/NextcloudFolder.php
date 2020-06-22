<?php


namespace OCA\WrikeSync\Wrike;


use JsonSerializable;

class NextcloudFolder implements JsonSerializable
{

    protected $internalId;
    protected $fullPath;
    protected $name;
    protected $parentId;
    protected $children;
    protected $mTime;

    public function __construct($internalId, $fullPath, $name, $parentId, $mTime, $children)
    {
        $this->internalId = $internalId;
        $this->fullPath = $fullPath;
        $this->name = $name;
        $this->parentId = $parentId;
        $this->mTime = $mTime;
        $this->children = $children;
    }

    public function getFullPath() {
        return $this->fullPath;
    }


    public function jsonSerialize()
    {
        return [
            "internalId" => $this->internalId,
            "fullPath" => $this->fullPath,
            "name" => $this->name,
            "parentId" => $this->parentId,
            "modificationTime" => $this->mTime,
            "children" => $this->children
        ];
    }
}