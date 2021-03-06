<?php
namespace OCA\WrikeSync\Wrike;

use JsonSerializable;

class WrikeFolder implements JsonSerializable
{

    private $folderId;
    private $title;
    private $scope;
    private $childIds;
    private $parentIds;

    function __construct($folderId, $title, $scope, $childIds, $parentIds)
    {
        $this->folderId = $folderId;
        $this->title = str_replace(array('/'), array('_'), $title);
        $this->scope = $scope;
        $this->childIds = $childIds;
        $this->parentIds = $parentIds;
    }

    /**
     * @return string
     */
    public function getFolderId(): string
    {
        return $this->folderId;
    }

    /**
     * @param string $folderId
     */
    public function setFolderId(string $folderId): void
    {
        $this->folderId = $folderId;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = str_replace(array('/'), array('_'), $title);;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @return mixed
     */
    public function getChildIds()
    {
        return $this->childIds;
    }

    /**
     * @param mixed $childIds
     */
    public function setChildIds($childIds): void
    {
        $this->childIds = $childIds;
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

    public function isFolderScope() : bool {
        return strcasecmp("WsFolder", $this->scope) == 0;
    }

    public function jsonSerialize()
    {
        return [
            "folderId" => $this->folderId,
            "title" => $this->title,
            "scope" => $this->scope,
            "childIds" => $this->childIds,
            "parentIds" => $this->parentIds,
            "parentId" => $this->getParentId()
        ];
    }
}