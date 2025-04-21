<?php
namespace OCA\DriverLicenseMgmt\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class ReminderSentMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'dlm_reminders_sent', ReminderSent::class);
    }

    /**
     * Check if a reminder has been sent for a driver
     *
     * @param int $driverId
     * @param int $notificationId
     * @param int $daysBefore
     * @return bool
     */
    public function hasReminderBeenSent(int $driverId, int $notificationId, int $daysBefore): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('driver_id', $qb->createNamedParameter($driverId)))
            ->andWhere($qb->expr()->eq('notification_id', $qb->createNamedParameter($notificationId)))
            ->andWhere($qb->expr()->eq('days_before', $qb->createNamedParameter($daysBefore)));

        $result = $qb->executeQuery();
        $count = (int)$result->fetchOne();
        $result->closeCursor();

        return $count > 0;
    }

    /**
     * Record a sent reminder
     *
     * @param int $driverId
     * @param int $notificationId
     * @param int $daysBefore
     * @return Entity
     */
    public function recordReminderSent(int $driverId, int $notificationId, int $daysBefore): Entity {
        $reminder = new ReminderSent();
        $reminder->setDriverId($driverId);
        $reminder->setNotificationId($notificationId);
        $reminder->setDaysBefore($daysBefore);
        $reminder->setSentAt(new \DateTime());

        return $this->insert($reminder);
    }
}