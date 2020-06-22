<?php
namespace OCA\WrikeSync\Wrike;

use JsonSerializable;

class WrikeSpace implements JsonSerializable
{

    private $spaceId;
    private $title;
    private $accessType;

    function __construct(string $spaceId, string $title, string $accessType)
    {
        $this->spaceId = $spaceId;
        $this->title = $title;
        $this->accessType = $accessType;
    }

    function getSpaceId() {
        return $this->spaceId;
    }

    function getSpaceTitle() {
        return $this->title;
    }

    public function getAccessType(): string
    {
        return $this->accessType;
    }

    public function isPersonalSpace() :bool {
        return strcasecmp("Personal", $this->accessType) == 0;
    }

    public function jsonSerialize()
    {
        return [
            "spaceId" => $this->spaceId,
            "title" => $this->title,
            "accessType" => $this->accessType
        ];
    }
}