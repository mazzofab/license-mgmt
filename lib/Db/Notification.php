<?php
namespace OCA\DriverLicenseMgmt\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Notification extends Entity implements JsonSerializable {
    protected $email;
    protected $phoneNumber;
    protected $active;
    protected $userId;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('active', 'boolean');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
            'active' => $this->active,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}