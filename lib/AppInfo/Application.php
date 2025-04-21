<?php
namespace OCA\DriverLicenseMgmt\AppInfo;

use OCA\DriverLicenseMgmt\BackgroundJob\SendReminders;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;

class Application extends App implements IBootstrap {
    public const APP_ID = 'driverlicensemgmt';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Nextcloud 31 doesn't support registerBackgroundJob in the registration context
        // We'll register it in the boot method instead
    }

    public function boot(IBootContext $context): void {
        // Register the background job if not already registered
        $jobList = $context->getAppContainer()->get(IJobList::class);
        if (!$jobList->has(SendReminders::class, null)) {
            $jobList->add(SendReminders::class);
        }
    }
}