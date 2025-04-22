<?php
namespace OCA\DriverLicenseMgmt\Notification;

use OCA\DriverLicenseMgmt\Db\Driver;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

class NotificationManager {
    /** @var IManager */
    private $notificationManager;
    
    /** @var IFactory */
    private $l10nFactory;
    
    /** @var IURLGenerator */
    private $urlGenerator;

    /**
     * @param IManager $notificationManager
     * @param IFactory $l10nFactory
     * @param IURLGenerator $urlGenerator
     */
    public function __construct(
        IManager $notificationManager,
        IFactory $l10nFactory,
        IURLGenerator $urlGenerator
    ) {
        $this->notificationManager = $notificationManager;
        $this->l10nFactory = $l10nFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Send a notification about an expiring driver license
     *
     * @param Driver $driver
     * @param int $daysRemaining
     * @param string $userId
     * @return void
     */
    public function sendExpiryNotification(Driver $driver, int $daysRemaining, string $userId): void {
        $notification = $this->notificationManager->createNotification();
        
        $notification->setApp('driverlicensemgmt')
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setObject('driver', (string) $driver->getId())
            ->setSubject('license_expiring', [
                'driver' => $driver->getId(),
                'name' => $driver->getName() . ' ' . $driver->getSurname(),
                'days' => $daysRemaining,
                'expiry_date' => $driver->getExpiryDate()->format('Y-m-d')
            ]);
        
        $this->notificationManager->notify($notification);
    }

    /**
     * Mark all notifications for a driver as processed
     *
     * @param int $driverId
     * @return void
     */
    public function markProcessed(int $driverId): void {
        $notification = $this->notificationManager->createNotification();
        
        $notification->setApp('driverlicensemgmt')
            ->setObject('driver', (string) $driverId);
        
        $this->notificationManager->markProcessed($notification);
    }
}