<?php


namespace OCA\WrikeSync\Service;

use Exception;

use OCA\WrikeSync\Db\WrikeFileNotificationMapper;
use OCA\WrikeSync\Db\WrikeFileNotification;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class WrikeFileNotificationService
{
    private $mapper;

    public function __construct(WrikeFileNotificationMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function findByNodeId(int $id) {
        try {
            return $this->mapper->findByNodeId($id);
        } catch(Exception $e) {
            return null;
        }
    }

    public function create($ncNodeId) {
        $wrikeFileNotification = new WrikeFileNotification();
        $wrikeFileNotification->setNcNodeId($ncNodeId);
        $wrikeFileNotification->setUtcTime(time());

        return $this->mapper->create($wrikeFileNotification);
    }

    public function delete(int $id) {
        try {
            $wrikeFileNotification = $this->mapper->find($id);
            $this->mapper->delete($wrikeFileNotification);
            return $wrikeFileNotification;
        } catch(Exception $e) {
            return null;
        }
    }
}