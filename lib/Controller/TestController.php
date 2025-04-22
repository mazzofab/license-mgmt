<?php
namespace OCA\DriverLicenseMgmt\Controller;

use OCA\DriverLicenseMgmt\AppInfo\Application;
use OCA\DriverLicenseMgmt\Db\DriverMapper;
use OCA\DriverLicenseMgmt\Notification\NotificationManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class TestController extends Controller {
    private $driverMapper;
    private $notificationManager;
    private $userId;

    public function __construct(
        IRequest $request,
        DriverMapper $driverMapper,
        NotificationManager $notificationManager,
        $userId
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->driverMapper = $driverMapper;
        $this->notificationManager = $notificationManager;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     *
     * @param int $driverId
     * @param int $days
     * @return DataResponse
     */
    public function sendNotification(int $driverId, int $days = 7): DataResponse {
        try {
            $driver = $this->driverMapper->find($driverId, $this->userId);
            $this->notificationManager->sendExpiryNotification($driver, $days, $this->userId);
            
            return new DataResponse([
                'status' => 'success',
                'message' => 'Notification sent successfully'
            ]);
        } catch (\Exception $e) {
            return new DataResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}