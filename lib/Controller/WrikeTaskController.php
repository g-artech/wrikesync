<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\Service\WrikeTaskService;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;

class WrikeTaskController extends Controller
{

    private $service;
    private $userId;
    private $logger;

    public function __construct(string $AppName, IRequest $request, WrikeTaskService $WrikeTaskService, ILogger $Logger, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $WrikeTaskService;
        $this->userId = $UserId;
        $this->logger = $Logger;
    }

    //Todo: Entfernung der Annotationen vor Übergabe!!!

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function task($id) {
        return new DataResponse($this->service->find($id));
    }
}