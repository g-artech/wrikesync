<?php

namespace OCA\WrikeSync\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class ConfigParameter extends Entity implements JsonSerializable
{

    public static $KEY_WRIKE_API_PROTOCOL = "wrike.api.protocol";
    public static $KEY_WRIKE_API_HOST = "wrike.api.host";
    public static $KEY_WRIKE_API_PORT = "wrike.api.port";
    public static $KEY_WRIKE_API_PATH = "wrike.api.path";

    public static $KEY_WRIKE_API_AUTH_TOKEN = "wrike.api.auth.token";

    public static $KEY_NEXTCLOUD_FILESYSTEM_ROOT_ID = "nextcloud.filesystem.root.id";
    public static $KEY_NEXTCLOUD_FILESYSTEM_SYNC_USER = "nextcloud.filesystem.sync.user";

    public static $KEY_CRONJOB_LAST_EXEC_START = "cronjob.laststart.sync";
    public static $KEY_CRONJOB_LASTRUN_SYNC = "cronjob.lastrun.sync";
    public static $KEY_CRONJOB_LASTRUN_LICENSE = "cronjob.lastrun.license";

    public static $KEY_LICENSE_KEY = "license.key";
    public static $KEY_LICENSE_ENCRYPTION_PASSWORD = "license.encryption.password";

    protected $key;
    protected $value;

    /**
     * @param mixed $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "key" => $this->key,
            "value" => $this->value
        ];
    }
}