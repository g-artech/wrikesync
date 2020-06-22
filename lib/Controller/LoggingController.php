<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\AppInfo\AppLogger;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;

class LoggingController extends Controller
{

    private $userId;
    private $logger;

    public function __construct(string $AppName, IRequest $request, ILogger $Logger, $UserId) {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->logger = $Logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function info($message) {
        AppLogger::logInfo($this->logger, $message);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function critical($message) {
        AppLogger::logCritical($this->logger, $message);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function error($message) {
        AppLogger::logError($this->logger, $message);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function warning($message) {
        AppLogger::logWarning($this->logger, $message);
    }
}