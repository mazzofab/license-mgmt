<?php
namespace OCA\DriverLicenseMgmt\Service;

use Exception;
use OCA\DriverLicenseMgmt\Db\Driver;
use OCA\DriverLicenseMgmt\Db\DriverMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class DriverService {
    private DriverMapper $mapper;

    public function __construct(DriverMapper $mapper) {
        $this->mapper = $mapper;
    }

    /**
     * Get all drivers for a user
     *
     * @param string $userId
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findAll(string $userId, ?int $limit = null, ?int $offset = null): array {
        return $this->mapper->findAll($userId, $limit, $offset);
    }

    /**
     * Get count of drivers for a user
     *
     * @param string $userId
     * @return int
     */
    public function count(string $userId): int {
        return $this->mapper->count($userId);
    }

    /**
     * Find a driver by ID
     *
     * @param int $id
     * @param string $userId
     * @return Driver
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function find(int $id, string $userId): Driver {
        try {
            return $this->mapper->find($id, $userId);
        } catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Could not find driver: ' . $e->getMessage());
        }
    }

    /**
     * Search drivers by name, surname or license number
     *
     * @param string $userId
     * @param string $query
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function search(string $userId, string $query, ?int $limit = null, ?int $offset = null): array {
        return $this->mapper->search($userId, $query, $limit, $offset);
    }

    /**
     * Create a new driver
     *
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phoneNumber
     * @param string $userId
     * @return Driver
     * @throws Exception
     */
    public function create(string $name, string $surname, string $licenseNumber, 
                          string $expiryDate, string $phoneNumber, string $userId): Driver {
        try {
            $driver = new Driver();
            $driver->setName($name);
            $driver->setSurname($surname);
            $driver->setLicenseNumber($licenseNumber);
            
            // Parse and set expiry date
            $expiry = \DateTime::createFromFormat('Y-m-d', $expiryDate);
            if ($expiry === false) {
                throw new Exception('Invalid expiry date format. Use YYYY-MM-DD.');
            }
            $driver->setExpiryDate($expiry);
            
            $driver->setPhoneNumber($phoneNumber);
            $driver->setUserId($userId);
            $driver->setCreatedAt(new \DateTime());
            $driver->setUpdatedAt(new \DateTime());
            
            return $this->mapper->insert($driver);
        } catch (Exception $e) {
            throw new Exception('Could not create driver: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing driver
     *
     * @param int $id
     * @param string $name
     * @param string $surname
     * @param string $licenseNumber
     * @param string $expiryDate
     * @param string $phoneNumber
     * @param string $userId
     * @return Driver
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function update(int $id, string $name, string $surname, string $licenseNumber,
                          string $expiryDate, string $phoneNumber, string $userId): Driver {
        try {
            $driver = $this->mapper->find($id, $userId);
            
            $driver->setName($name);
            $driver->setSurname($surname);
            $driver->setLicenseNumber($licenseNumber);
            
            // Parse and set expiry date
            $expiry = \DateTime::createFromFormat('Y-m-d', $expiryDate);
            if ($expiry === false) {
                throw new Exception('Invalid expiry date format. Use YYYY-MM-DD.');
            }
            $driver->setExpiryDate($expiry);
            
            $driver->setPhoneNumber($phoneNumber);
            $driver->setUpdatedAt(new \DateTime());
            
            return $this->mapper->update($driver);
        } catch (DoesNotExistException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Could not update driver: ' . $e->getMessage());
        }
    }

    /**
     * Delete a driver
     *
     * @param int $id
     * @param string $userId
     * @return Driver
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function delete(int $id, string $userId): Driver {
        try {
            $driver = $this->mapper->find($id, $userId);
            return $this->mapper->delete($driver);
        } catch (DoesNotExistException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Could not delete driver: ' . $e->getMessage());
        }
    }

    /**
     * Find drivers with expiring licenses
     *
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @return array
     */
    public function findExpiring(\DateTime $fromDate, \DateTime $toDate): array {
        return $this->mapper->findExpiring($fromDate, $toDate);
    }
}