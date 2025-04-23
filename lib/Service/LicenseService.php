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
     * @param string $userId User ID
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phoneNumber
     * @return Driver
     */
    public function addLicense(string $userId, string $name, string $surname, string $licenseNumber, string $expiryDate, string $phoneNumber = ''): Driver {
        $driver = new Driver();
        $driver->setName($name);
        $driver->setSurname($surname);
        $driver->setLicenseNumber($licenseNumber);
        $driver->setExpiryDate($expiryDate);
        $driver->setPhoneNumber($phoneNumber);
        $driver->setUserId($userId);
        $driver->setCreatedAt(new \DateTime());
        $driver->setUpdatedAt(new \DateTime());
        
        return $this->driverMapper->insert($driver);
    }

    /**
     * Update an existing driver license
     *
     * @param int $id
     * @param string $userId User ID
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phoneNumber
     * @return Driver
     * @throws DoesNotExistException
     */
    public function updateLicense(int $id, string $userId, string $name, string $surname, string $licenseNumber, string $expiryDate, string $phoneNumber = ''): Driver {
        try {
            $driver = $this->driverMapper->find($id, $userId);
            $driver->setName($name);
            $driver->setSurname($surname);
            $driver->setLicenseNumber($licenseNumber);
            $driver->setExpiryDate($expiryDate);
            $driver->setPhoneNumber($phoneNumber);
            $driver->setUpdatedAt(new \DateTime());
            
            return $this->driverMapper->update($driver);
        } catch (DoesNotExistException $e) {
            $this->logger->error('Could not update driver with id ' . $id . ': ' . $e->getMessage());
            throw $e;
        } catch (MultipleObjectsReturnedException $e) {
            $this->logger->error('Multiple drivers found with id ' . $id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all drivers for a user
     *
     * @param string $userId User ID
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllLicenses(string $userId, int $limit = 50, int $offset = 0): array {
        return $this->driverMapper->findAll($userId, $limit, $offset);
    }

    /**
     * Get a driver by ID
     *
     * @param int $id
     * @param string $userId User ID
     * @return Driver
     * @throws DoesNotExistException
     */
    public function getLicense(int $id, string $userId): Driver {
        try {
            return $this->driverMapper->find($id, $userId);
        } catch (DoesNotExistException $e) {
            $this->logger->error('Could not find driver with id ' . $id . ': ' . $e->getMessage());
            throw $e;
        } catch (MultipleObjectsReturnedException $e) {
            $this->logger->error('Multiple drivers found with id ' . $id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a driver by ID
     *
     * @param int $id
     * @param string $userId User ID
     * @return Driver
     * @throws DoesNotExistException
     */
    public function deleteLicense(int $id, string $userId): Driver {
        try {
            $driver = $this->driverMapper->find($id, $userId);
            return $this->driverMapper->delete($driver);
        } catch (DoesNotExistException $e) {
            $this->logger->error('Could not delete driver with id ' . $id . ': ' . $e->getMessage());
            throw $e;
        } catch (MultipleObjectsReturnedException $e) {
            $this->logger->error('Multiple drivers found with id ' . $id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search for drivers
     *
     * @param string $userId User ID
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchLicenses(string $userId, string $query, int $limit = 50, int $offset = 0): array {
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