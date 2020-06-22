<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\Service\WrikeFolderService;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;

class WrikeFolderController extends Controller
{

    private $service;
    private $userId;
    private $logger;

    public function __construct(string $AppName, IRequest $request, WrikeFolderService $WrikeFolderService, ILogger $Logger, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $WrikeFolderService;
        $this->userId = $UserId;
        $this->logger = $Logger;
    }

    //Todo: Entfernung der Annotationen vor Übergabe!!!

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        return new DataResponse($this->service->findAll());
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function folder($id) {
        return new DataResponse($this->service->find($id));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function subfolders($id) {
        return new DataResponse($this->service->getSubFoldersOfFolderId($id));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function tasks($id) {
        return new DataResponse($this->service->getTasksOfFolderId($id));
    }
}