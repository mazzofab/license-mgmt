<?php
/**
 * Test script for sending driver license expiry notifications
 * This script can be used to test notification functionality
 * 
 * Usage: php test-notification.php <driver_id> <days_before>
 * Example: php test-notification.php 1 7
 */

define('PHPUNIT_RUN', 1);
require_once __DIR__ . '/../../lib/base.php';

// Get command line arguments
$driverId = isset($argv[1]) ? (int)$argv[1] : 0;
$daysBefore = isset($argv[2]) ? (int)$argv[2] : 7;

if ($driverId <= 0) {
    echo "Error: Please provide a valid driver ID as the first argument.\n";
    echo "Usage: php test-notification.php <driver_id> <days_before>\n";
    exit(1);
}

if (!in_array($daysBefore, [1, 7, 30])) {
    echo "Warning: Days before should ideally be 1, 7, or 30. Using $daysBefore instead.\n";
}

// Initialize the app
$app = new \OCA\DriverLicenseMgmt\AppInfo\Application();
$container = $app->getContainer();

try {
    // Get the driver
    $driverMapper = $container->get(\OCA\DriverLicenseMgmt\Db\DriverMapper::class);
    $driver = null;
    
    try {
        // Try to find the driver (without user ID check)
        $qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
        $qb->select('*')
            ->from('dlm_drivers')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($driverId)));
        
        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();
        
        if (!$row) {
            throw new \Exception("Driver with ID $driverId not found");
        }
        
        // Convert to Driver object
        $driver = new \OCA\DriverLicenseMgmt\Db\Driver();
        $driver->setId($row['id']);
        $driver->setName($row['name']);
        $driver->setSurname($row['surname']);
        $driver->setLicenseNumber($row['license_number']);
        $driver->setExpiryDate(new \DateTime($row['expiry_date']));
        $driver->setPhoneNumber($row['phone_number']);
        $driver->setUserId($row['user_id']);
        
    } catch (\Exception $e) {
        echo "Error finding driver: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    echo "Found driver: " . $driver->getName() . " " . $driver->getSurname() . "\n";
    
    // Get the notification manager
    $notificationManager = $container->get(\OCA\DriverLicenseMgmt\Notification\NotificationManager::class);
    
    // Send the notification
    $notificationManager->sendExpiryNotification($driver, $daysBefore, $driver->getUserId());
    
    echo "Notification sent successfully to user " . $driver->getUserId() . "\n";
    echo "Check the notification bell in the Nextcloud UI.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}