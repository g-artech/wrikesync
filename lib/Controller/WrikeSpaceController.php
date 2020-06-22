<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\Service\WrikeSpaceService;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;

class WrikeSpaceController extends Controller
{

    private $service;
    private $userId;
    private $logger;

    public function __construct(string $AppName, IRequest $request, WrikeSpaceService $WrikeSpaceService, ILogger $Logger, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $WrikeSpaceService;
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

}