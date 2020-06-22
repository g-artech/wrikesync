<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\Db\ConfigParameter;
use OCA\WrikeSync\Service\ConfigParameterService;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class ConfigParameterController extends Controller
{

    private $service;
    private $userId;

    public function __construct(string $AppName, IRequest $request, ConfigParameterService $service, $UserId) {
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
    public function currentUser() {
        return new DataResponse($this->userId);
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
            return new DataResponse([], Http::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function showByKey($key) {
        try {
            $entity = $this->service->findValueForKey($key);
            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_NOT_FOUND);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create($key, $value) {
        try {
            $entity = $this->service->create($key, $value);

            if ($key == ConfigParameter::$KEY_NEXTCLOUD_FILESYSTEM_ROOT_ID) {
                $this->setSyncUser();
            }

            if ($entity != null) {
                return new DataResponse($entity);
            } else {
                return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
            }
        } catch(Exception $e) {
            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    private function setSyncUser() {
        $currentParameter = $this->service->findValueForKey(ConfigParameter::$KEY_NEXTCLOUD_FILESYSTEM_SYNC_USER);

        if ($currentParameter != null) {
            $this->destroy($currentParameter->getId());
        }

        $this->create(ConfigParameter::$KEY_NEXTCLOUD_FILESYSTEM_SYNC_USER, $this->userId);
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