<?php
namespace OCA\DriverLicenseMgmt\Controller;

use Exception;
use OCA\DriverLicenseMgmt\AppInfo\Application;
use OCA\DriverLicenseMgmt\Service\DriverService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class DriverController extends Controller {
    private DriverService $service;
    private string $userId;

    public function __construct(IRequest $request, DriverService $service, $userId) {
        parent::__construct(Application::APP_ID, $request);
        $this->service = $service;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     *
     * @param int|null $limit
     * @param int|null $offset
     * @return DataResponse
     */
    public function index(?int $limit = null, ?int $offset = null): DataResponse {
        return new DataResponse([
            'data' => $this->service->findAll($this->userId, $limit, $offset),
            'total' => $this->service->count($this->userId)
        ]);
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
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phoneNumber
     * @return DataResponse
     */
    public function create(string $name, string $surname, string $licenseNumber, 
                        string $expiryDate, string $phoneNumber): DataResponse {
        try {
            $driver = $this->service->create(
                $name, $surname, $licenseNumber, $expiryDate, $phoneNumber, $this->userId
            );
            return new DataResponse($driver, Http::STATUS_CREATED);
        } catch (Exception $e) {
            return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     *
     * @param int $id
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phoneNumber
     * @return DataResponse
     */
    public function update(int $id, string $name, string $surname, string $licenseNumber, 
                        string $expiryDate, string $phoneNumber): DataResponse {
        try {
            $driver = $this->service->update(
                $id, $name, $surname, $licenseNumber, $expiryDate, $phoneNumber, $this->userId
            );
            return new DataResponse($driver);
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
            $driver = $this->service->delete($id, $this->userId);
            return new DataResponse($driver);
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
     * @param string $query
     * @param int|null $limit
     * @param int|null $offset
     * @return DataResponse
     */
    public function search(string $query, ?int $limit = null, ?int $offset = null): DataResponse {
        return new DataResponse($this->service->search($this->userId, $query, $limit, $offset));
    }