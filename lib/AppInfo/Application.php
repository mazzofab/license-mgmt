<?php
namespace OCA\DriverLicenseMgmt\AppInfo;

use OCA\DriverLicenseMgmt\BackgroundJob\SendReminders;
use OCA\DriverLicenseMgmt\Notification\NotificationProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use OCP\Notification\IManager;
use OCP\Util;

class Application extends App implements IBootstrap {
    public const APP_ID = 'driverlicensemgmt';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Register notification provider
        $context->registerNotifierService(NotificationProvider::class);
    }

    public function boot(IBootContext $context): void {
        // Register the background job if not already registered
        $jobList = $context->getAppContainer()->get(IJobList::class);
        if (!$jobList->has(SendReminders::class, null)) {
            $jobList->add(SendReminders::class);
        }

        // Register the notifier with the notification manager
        $notificationManager = $context->getServerContainer()->get(IManager::class);
        $notificationManager->registerNotifierService(NotificationProvider::class);
        
        // Register JavaScript files to ensure they have proper CSP nonces
        Util::addScript(self::APP_ID, 'script');
        Util::addScript(self::APP_ID, 'importCSV');
        
        // Register CSS files
        Util::addStyle(self::APP_ID, 'style');
    }
}