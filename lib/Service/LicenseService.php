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
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phone
     * @return Driver
     */
    public function addLicense(string $name, string $surname, string $licenseNumber, string $expiryDate, string $phone = ''): Driver {
        $driver = new Driver();
        $driver->setName($name);
        $driver->setSurname($surname);
        $driver->setLicenseNumber($licenseNumber);
        $driver->setExpiryDate($expiryDate);
        $driver->setPhoneNumber($phone); // Assuming your Driver entity uses phoneNumber, not phone
        $driver->setUserId(\OC::$server->getUserSession()->getUser()->getUID()); // Get current user ID
        $driver->setCreatedAt(new \DateTime());
        $driver->setUpdatedAt(new \DateTime());
        
        return $this->driverMapper->insert($driver);
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