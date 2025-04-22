<?php
namespace OCA\DriverLicenseMgmt\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class ReminderSent extends Entity implements JsonSerializable {
    protected $driverId;
    protected $notificationId;
    protected $daysBefore;
    protected $sentAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('driverId', 'integer');
        $this->addType('notificationId', 'integer');
        $this->addType('daysBefore', 'integer');
        $this->addType('sentAt', 'datetime');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'driverId' => $this->driverId,
            'notificationId' => $this->notificationId,
            'daysBefore' => $this->daysBefore,
            'sentAt' => $this->sentAt,
        ];
    }
}