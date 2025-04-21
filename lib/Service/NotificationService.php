<?php
namespace OCA\DriverLicenseMgmt\Service;

use Exception;
use OCA\DriverLicenseMgmt\Db\Notification;
use OCA\DriverLicenseMgmt\Db\NotificationMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class NotificationService {
    private NotificationMapper $mapper;

    public function __construct(NotificationMapper $mapper) {
        $this->mapper = $mapper;
    }

    /**
     * Get all notification recipients for a user
     *
     * @param string $userId
     * @return array
     */
    public function findAll(string $userId): array {
        return $this->mapper->findAll($userId);
    }

    /**
     * Get active notification recipients
     *
     * @return array
     */
    public function findActive(): array {
        return $this->mapper->findActive();
    }

    /**
     * Find a notification recipient by id
     *
     * @param int $id
     * @param string $userId
     * @return Notification
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function find(int $id, string $userId): Notification {
        try {
            return $this->mapper->find($id, $userId);
        } catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Could not find notification recipient: ' . $e->getMessage());
        }
    }

    /**
     * Create a new notification recipient
     *
     * @param string $email
     * @param string $phoneNumber
     * @param bool $active
     * @param string $userId
     * @return Notification
     * @throws Exception
     */
    public function create(string $email, string $phoneNumber, bool $active, string $userId): Notification {
        try {
            // Check if email already exists
            if ($this->mapper->emailExists($email, $userId)) {
                throw new Exception('A notification recipient with this email already exists.');
            }
            
            $notification = new Notification();
            $notification->setEmail($email);
            $notification->setPhoneNumber($phoneNumber);
            $notification->setActive($active);
            $notification->setUserId($userId);
            $notification->setCreatedAt(new \DateTime());
            $notification->setUpdatedAt(new \DateTime());
            
            return $this->mapper->insert($notification);
        } catch (Exception $e) {
            throw new Exception('Could not create notification recipient: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing notification recipient
     *
     * @param int $id
     * @param string $email
     * @param string $phoneNumber
     * @param bool $active
     * @param string $userId
     * @return Notification
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function update(int $id, string $email, string $phoneNumber, bool $active, string $userId): Notification {
        try {
            $notification = $this->mapper->find($id, $userId);
            
            // Check if email already exists (excluding this record)
            if ($email !== $notification->getEmail() && $this->mapper->emailExists($email, $userId, $id)) {
                throw new Exception('A notification recipient with this email already exists.');
            }
            
            $notification->setEmail($email);
            $notification->setPhoneNumber($phoneNumber);
            $notification->setActive($active);
            $notification->setUpdatedAt(new \DateTime());
            
            return $this->mapper->update($notification);
        } catch (DoesNotExistException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Could not update notification recipient: ' . $e->getMessage());
        }
    }

    /**
     * Delete a notification recipient
     *
     * @param int $id
     * @param string $userId
     * @return Notification
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function delete(int $id, string $userId): Notification {
        try {
            $notification = $this->mapper->find($id, $userId);
            return $this->mapper->delete($notification);
        } catch (DoesNotExistException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Could not delete notification recipient: ' . $e->getMessage());
        }
    }
}