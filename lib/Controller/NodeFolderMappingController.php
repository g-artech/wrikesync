<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\Service\NodeFolderMappingService;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

/**
 * Controller class for CRUD of NodeSpaceMapping entities.
 * Used for client side HTTP requests.
 *
 * Class NodeFolderMappingController
 * @package OCA\WrikeSync\Controller
 */
class NodeFolderMappingController extends Controller
{
    private $service;
    private $userId;

    public function __construct(string $AppName, IRequest $request, NodeFolderMappingService $service, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->userId = $UserId;
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
    public function forFolderId($id) {
        try {
            $entity = $this->service->findMappingByFolderId($id);
            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_NOT_FOUND);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show($id) {
        try {
            $entity = $this->service->find($id);
            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_NOT_FOUND);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST on /mappings with application/json body to create new entries.
     * Please note to be sure that all keys AND values are quoted as strings in your JSON!
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param int $ncNodeId
     * @param string $wrFolderId
     */
    public function create($ncNodeId, $wrFolderId) {
        try {
            $entity = $this->service->create($ncNodeId, $wrFolderId);
            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createForName($ncNodeName, $wrFolderId) {
        try {
            $entity = $this->service->createForName($ncNodeName, $wrFolderId);
            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function destroy($id) {
        try {
            $entity = $this->service->delete($id);
            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_NOT_FOUND);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}