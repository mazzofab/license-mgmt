<?php
namespace OCA\DriverLicenseMgmt\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class NotificationMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'dlm_notifications', Notification::class);
    }

    /**
     * Find all notification recipients for a user
     *
     * @param string $userId
     * @return Entity[] notification recipients
     */
    public function findAll(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('email', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find a notification recipient by id and user id
     *
     * @param int $id
     * @param string $userId
     * @return Entity notification recipient
     * @throws DoesNotExistException
     */
    public function find(int $id, string $userId): Entity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntity($qb);
    }

    /**
     * Find all active notification recipients
     *
     * @return Entity[] active notification recipients
     */
    public function findActive(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('active', $qb->createNamedParameter(true)))
            ->orderBy('email', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Check if an email already exists for given user
     *
     * @param string $email
     * @param string $userId
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, string $userId, ?int $excludeId = null): bool {
        $qb = $this->db->getQueryBuilder();
        // Use count() function instead of 'COUNT(*)' string
        $qb->select($qb->func()->count('*', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('email', $qb->createNamedParameter($email)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        if ($excludeId !== null) {
            $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId)));
        }

        $result = $qb->executeQuery();
        $count = (int)$result->fetchColumn();
        $result->closeCursor();

        return $count > 0;
    }
}