<?php
namespace OCA\DriverLicenseMgmt\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Driver extends Entity implements JsonSerializable {
    protected $name;
    protected $surname;
    protected $licenseNumber;
    protected $expiryDate;
    protected $phoneNumber;
    protected $userId;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('expiryDate', 'date');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'licenseNumber' => $this->licenseNumber,
            'expiryDate' => $this->expiryDate,
            'phoneNumber' => $this->phoneNumber,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}