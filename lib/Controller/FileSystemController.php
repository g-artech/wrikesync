<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\AppInfo\AppLogger;
use OCA\WrikeSync\Cron\SynchronizationTask;
use OCA\WrikeSync\Service\FileSystemService;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;

class FileSystemController extends Controller
{

    private $service;
    private $userId;
    private $logger;
    private $syncTask;
    private $licenseController;

    public function __construct(string $AppName, IRequest $request, FileSystemService $FileSystemServiceService, SynchronizationTask $SynchronizationTask, LicenseController $licenseController, ILogger $Logger, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $FileSystemServiceService;
        $this->userId = $UserId;
        $this->logger = $Logger;
        $this->syncTask = $SynchronizationTask;
        $this->licenseController = $licenseController;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function sync() {
        $this->syncTask->doSync();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function license() {
        $this->licenseController->checkLicenseAndConfig();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function syncRoot() {
        return new DataResponse($this->service->getNextcloudSyncRootFolder());
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function folder($id) {
        return new DataResponse($this->service->getNextcloudFolderOfId($id));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function children($id) {
        return new DataResponse($this->service->getNextcloudChildrenOfFolderId($id));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create($id, $name) {
        $this->service->createFsFolderInFsFolderId($id, $name);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function rename($id, $name) {
        $this->service->renameFsFolderId($id, $name);
    }
}