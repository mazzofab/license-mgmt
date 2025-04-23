<?php
namespace OCA\DriverLicenseMgmt\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'driverlicensemgmt';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Register your controllers, services, etc.
    }

    public function boot(IBootContext $context): void {
        // Dynamic initialization of your app
        
        // If you need to add additional script loading logic, use:
        \OCP\Util::addScript(self::APP_ID, 'script');
        \OCP\Util::addScript(self::APP_ID, 'importCSV');
        
        // And for styles:
        \OCP\Util::addStyle(self::APP_ID, 'style');
    }
}