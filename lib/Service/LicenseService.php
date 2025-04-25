<?php

namespace OCA\DriverLicenseMgmt\Service;

use OCP\IDBConnection;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCA\DriverLicenseMgmt\Db\Driver;
use OCA\DriverLicenseMgmt\Db\DriverMapper;
use Psr\Log\LoggerInterface;

class LicenseService {
    /** @var DriverMapper */
    private $driverMapper;
    
    /** @var LoggerInterface */
    private $logger;
    
    /** @var IDBConnection */
    private $db;

    /**
     * LicenseService constructor.
     *
     * @param DriverMapper $driverMapper
     * @param LoggerInterface $logger
     * @param IDBConnection $db
     */
    public function __construct(DriverMapper $driverMapper, LoggerInterface $logger, IDBConnection $db) {
        $this->driverMapper = $driverMapper;
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
 * Add a new driver license
 *
 * @param string $userId
 * @param string $name
 * @param string $surname
 * @param string $licenseNumber
 * @param string $expiryDate
 * @param string $phone
 * @return Driver
 */
public function addLicense(string $userId, string $name, string $surname, string $licenseNumber, string $expiryDate, string $phone = ''): Driver {
    try {
        $driver = new Driver();
        $driver->setName($name);
        $driver->setSurname($surname);
        $driver->setLicenseNumber($licenseNumber);
        
        // Properly parse the expiry date
        if ($this->validateDate($expiryDate)) {
            $date = \DateTime::createFromFormat('Y-m-d', $expiryDate);
            if ($date) {
                $driver->setExpiryDate($date->format('Y-m-d'));
            } else {
                throw new \Exception("Invalid date format: " . $expiryDate);
            }
        } else {
            // Try to parse other date formats
            $dateFormats = [
                'd/m/Y', // 31/12/2023
                'm/d/Y', // 12/31/2023
                'd-m-Y', // 31-12-2023
                'm-d-Y', // 12-31-2023
                'd.m.Y', // 31.12.2023
                'Y.m.d'  // 2023.12.31
            ];
            
            $validDate = false;
            foreach ($dateFormats as $format) {
                $date = \DateTime::createFromFormat($format, $expiryDate);
                if ($date) {
                    $driver->setExpiryDate($date->format('Y-m-d'));
                    $validDate = true;
                    break;
                }
            }
            
            if (!$validDate) {
                throw new \Exception("Invalid date format: " . $expiryDate);
            }
        }
        
        $driver->setPhoneNumber($phone); // Assuming your Driver entity uses phoneNumber
        $driver->setUserId($userId);
        
        // Handle date objects correctly
        $now = new \DateTime();
        $driver->setCreatedAt($now->format('Y-m-d H:i:s'));
        $driver->setUpdatedAt($now->format('Y-m-d H:i:s'));
        
        return $this->driverMapper->insert($driver);
    } catch (\Exception $e) {
        $this->logger->error('Error adding license: ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
        throw $e;
    }
}

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date
     * @return bool
     */
    private function validateDate($date) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            return $dateTime && $dateTime->format('Y-m-d') === $date;
        }
        return false;
    }

    /**
     * Update an existing driver license
     *
     * @param int $id
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phone
     * @param bool $active
     * @return Driver
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function updateLicense(int $id, string $name, string $surname, string $licenseNumber, string $expiryDate, string $phone = ''): Driver {
        try {
            $userId = \OC::$server->getUserSession()->getUser()->getUID();
            $driver = $this->driverMapper->find($id, $userId);
            $driver->setName($name);
            $driver->setSurname($surname);
            $driver->setLicenseNumber($licenseNumber);
            $driver->setExpiryDate($expiryDate);
            $driver->setPhoneNumber($phone);
            $driver->setUpdatedAt(new \DateTime());
            
            return $this->driverMapper->update($driver);
        } catch (DoesNotExistException $e) {
            $this->logger->error('Could not update driver with id ' . $id . ': ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            throw $e;
        } catch (MultipleObjectsReturnedException $e) {
            $this->logger->error('Multiple drivers found with id ' . $id . ': ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            throw $e;
        }
    }

    /**
     * Get all drivers
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllLicenses(int $limit = 50, int $offset = 0): array {
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        return $this->driverMapper->findAll($userId, $limit, $offset);
    }

    /**
     * Get a driver by ID
     *
     * @param int $id
     * @return Driver
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function getLicense(int $id): Driver {
        try {
            $userId = \OC::$server->getUserSession()->getUser()->getUID();
            return $this->driverMapper->find($id, $userId);
        } catch (DoesNotExistException $e) {
            $this->logger->error('Could not find driver with id ' . $id . ': ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            throw $e;
        } catch (MultipleObjectsReturnedException $e) {
            $this->logger->error('Multiple drivers found with id ' . $id . ': ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            throw $e;
        }
    }

    /**
     * Delete a driver by ID
     *
     * @param int $id
     * @return Driver
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function deleteLicense(int $id): Driver {
        try {
            $userId = \OC::$server->getUserSession()->getUser()->getUID();
            $driver = $this->driverMapper->find($id, $userId);
            return $this->driverMapper->delete($driver);
        } catch (DoesNotExistException $e) {
            $this->logger->error('Could not delete driver with id ' . $id . ': ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            throw $e;
        } catch (MultipleObjectsReturnedException $e) {
            $this->logger->error('Multiple drivers found with id ' . $id . ': ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            throw $e;
        }
    }

    /**
     * Search for drivers
     *
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchLicenses(string $query, int $limit = 50, int $offset = 0): array {
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        return $this->driverMapper->search($userId, $query, $limit, $offset);
    }

    /**
     * Get licenses expiring within a specific number of days
     *
     * @param int $days
     * @return array
     */
    public function getLicensesExpiringInDays(int $days): array {
        $now = new \DateTime();
        $future = clone $now;
        $future->add(new \DateInterval('P' . $days . 'D'));
        
        return $this->driverMapper->findExpiring($now, $future);
    }
}