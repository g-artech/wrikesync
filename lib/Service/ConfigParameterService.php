<?php


namespace OCA\WrikeSync\Service;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\WrikeSync\Db\ConfigParameter;
use OCA\WrikeSync\Db\ConfigParameterMapper;

use Exception;

class ConfigParameterService
{

    private $mapper;
    private $userId;

    public function __construct(ConfigParameterMapper $mapper, $UserId) {
        $this->mapper = $mapper;
        $this->userId = $UserId;
    }

    public function findAll() {
        return $this->mapper->findAll();
    }

    private function handleException ($e) {
        if ($e instanceof DoesNotExistException ||
            $e instanceof MultipleObjectsReturnedException) {
            throw new NotFoundException($e->getMessage());
        } else {
            throw $e;
        }
    }

    public function findByKey(string $key) {
        try {
            return $this->mapper->findValueForKey($key);
        } catch(Exception $e) {
            return null;
        }
    }

    public function findValueForKey(string $key) {
        try {
            return $this->mapper->findValueForKey($key)->getValue();
        } catch(Exception $e) {
            return null;
        }
    }

    public function find(int $id) {
        try {
            return $this->mapper->find($id);
        } catch(Exception $e) {
            return null;
        }
    }

    public function create($key, $value) {
        $parameter = new ConfigParameter();
        $parameter->setKey($key);
        $parameter->setValue($value);

        return $this->mapper->create($parameter);
    }

    public function delete(int $id) {
        try {
            $nodeSpaceMapping = $this->mapper->find($id);
            $this->mapper->delete($nodeSpaceMapping);
            return $nodeSpaceMapping;
        } catch(Exception $e) {
            return null;
        }
    }

    public function updateLastRunForSync() {
        $this->updateLastRun(ConfigParameter::$KEY_CRONJOB_LASTRUN_SYNC);
    }

    public function updateLastRunForLicense() {
        $this->updateLastRun(ConfigParameter::$KEY_CRONJOB_LASTRUN_LICENSE);
    }

    private function updateLastRun($key) {
        $existingParameter = $this->findByKey($key);

        if ($existingParameter != null) {
            $this->delete($existingParameter->getId());
        }

        $this->create($key, time());
    }
}