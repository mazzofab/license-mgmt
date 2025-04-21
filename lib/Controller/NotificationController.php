<?php
namespace OCA\DriverLicenseMgmt\Controller;

use Exception;
use OCA\DriverLicenseMgmt\AppInfo\Application;
use OCA\DriverLicenseMgmt\Service\NotificationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class NotificationController extends Controller {
    private NotificationService $service;
    private string $userId;

    public function __construct(IRequest $request, NotificationService $service, $userId) {
        parent::__construct(Application::APP_ID, $request);
        $this->service = $service;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     *
     * @return DataResponse
     */
    public function index(): DataResponse {
        return new DataResponse($this->service->findAll($this->userId));
    }

    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @return DataResponse
     */
    public function show(int $id): DataResponse {
        try {
            return new DataResponse($this->service->find($id, $this->userId));
        } catch (Exception $e) {
            return new DataResponse(['message' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * @NoAdminRequired
     *
     * @param string $email
     * @param string $phoneNumber
     * @param bool $active
     * @return DataResponse
     */
    public function create(string $email, string $phoneNumber, bool $active = true): DataResponse {
        try {
            $notification = $this->service->create($email, $phoneNumber, $active, $this->userId);
            return new DataResponse($notification, Http::STATUS_CREATED);
        } catch (Exception $e) {
            return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @param string $email
     * @param string $phoneNumber
     * @param bool $active
     * @return DataResponse
     */
    public function update(int $id, string $email, string $phoneNumber, bool $active = true): DataResponse {
        try {
            $notification = $this->service->update($id, $email, $phoneNumber, $active, $this->userId);
            return new DataResponse($notification);
        } catch (Exception $e) {
            $code = ($e instanceof \OCP\AppFramework\Db\DoesNotExistException) 
                ? Http::STATUS_NOT_FOUND 
                : Http::STATUS_BAD_REQUEST;
            return new DataResponse(['message' => $e->getMessage()], $code);
        }
    }

    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @return DataResponse
     */
    public function destroy(int $id): DataResponse {
        try {
            $notification = $this->service->delete($id, $this->userId);
            return new DataResponse($notification);
        } catch (Exception $e) {
            $code = ($e instanceof \OCP\AppFramework\Db\DoesNotExistException) 
                ? Http::STATUS_NOT_FOUND 
                : Http::STATUS_BAD_REQUEST;
            return new DataResponse(['message' => $e->getMessage()], $code);
        }
    }
}