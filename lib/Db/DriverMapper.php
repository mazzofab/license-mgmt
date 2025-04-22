<?php
namespace OCA\DriverLicenseMgmt\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class DriverMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'dlm_drivers', Driver::class);
    }

    /**
     * Find all drivers belonging to a user
     *
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @return Entity[] drivers
     */
    public function findAll(string $userId, int $limit = null, int $offset = null): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('surname', 'ASC')
            ->addOrderBy('name', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $this->findEntities($qb);
    }

    /**
     * Find driver by id and user id
     *
     * @param int $id
     * @param string $userId
     * @return Entity driver
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
     * Find drivers with expiring licenses
     *
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @return Entity[] drivers
     */
    public function findExpiring(\DateTime $fromDate, \DateTime $toDate): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->gte('expiry_date', $qb->createNamedParameter($fromDate, IQueryBuilder::PARAM_DATE)))
            ->andWhere($qb->expr()->lte('expiry_date', $qb->createNamedParameter($toDate, IQueryBuilder::PARAM_DATE)))
            ->orderBy('expiry_date', 'ASC')
            ->addOrderBy('surname', 'ASC')
            ->addOrderBy('name', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Count total drivers for a user
     * 
     * @param string $userId
     * @return int
     */
    public function count(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $count = (int)$result->fetchOne();
        $result->closeCursor();
        
        return $count;
    }

    /**
     * Search drivers by name, surname, license number or phone number
     * 
     * @param string $userId
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return Entity[] drivers
     */
    public function search(string $userId, string $query, int $limit = null, int $offset = null): array {
        if (empty($query) || trim($query) === '') {
            return $this->findAll($userId, $limit, $offset);
        }
        
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->iLike('name', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query) . '%')),
                    $qb->expr()->iLike('surname', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query) . '%')),
                    $qb->expr()->iLike('license_number', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query) . '%')),
                    $qb->expr()->iLike('phone_number', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query) . '%'))
                )
            )
            ->orderBy('surname', 'ASC')
            ->addOrderBy('name', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        try {
            return $this->findEntities($qb);
        } catch (\Exception $e) {
            // Log the error
            \OC::$server->getLogger()->error('Error searching drivers: ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
            // Return empty array on error
            return [];
        }
    }
}