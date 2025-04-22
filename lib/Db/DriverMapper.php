<?php
namespace OCA\DriverLicenseMgmt\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class DriverMapper extends QBMapper {
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(IDBConnection $db, LoggerInterface $logger) {
        parent::__construct($db, 'dlm_drivers', Driver::class);
        $this->logger = $logger;
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
        // If the search query is empty, return all drivers
        if (empty($query) || trim($query) === '') {
            return $this->findAll($userId, $limit, $offset);
        }
        
        // We'll log what's happening to help troubleshoot
        $this->logger->debug('Searching for drivers: query="' . $query . '", userId="' . $userId . '"', ['app' => 'driverlicensemgmt']);
        
        try {
            // Get the database platform
            $platform = $this->db->getDatabasePlatform();
            $qb = $this->db->getQueryBuilder();
            
            // Prepare the search query - normalize it to lowercase for case-insensitive search
            $searchQuery = '%' . strtolower($query) . '%';
            $this->logger->debug('Normalized search query: ' . $searchQuery, ['app' => 'driverlicensemgmt']);
            
            // Build the query with LOWER() function for case-insensitive search
            $qb->select('*')
                ->from($this->getTableName())
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like($qb->func()->lower('name'), $qb->createNamedParameter($searchQuery)),
                        $qb->expr()->like($qb->func()->lower('surname'), $qb->createNamedParameter($searchQuery)),
                        $qb->expr()->like($qb->func()->lower('license_number'), $qb->createNamedParameter($searchQuery)),
                        $qb->expr()->like($qb->func()->lower('phone_number'), $qb->createNamedParameter($searchQuery))
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
            
            // Log the SQL query for troubleshooting
            $sql = $qb->getSQL();
            $this->logger->debug('Search SQL: ' . $sql, ['app' => 'driverlicensemgmt']);
            
            $results = $this->findEntities($qb);
            
            // Log the number of results found
            $this->logger->debug('Search results count: ' . count($results), ['app' => 'driverlicensemgmt']);
            
            return $results;
            
        } catch (\Exception $e) {
            // Log the error
            $this->logger->error('Error searching drivers: ' . $e->getMessage(), [
                'app' => 'driverlicensemgmt',
                'exception' => $e
            ]);
            
            // Try a different approach if the first one fails
            try {
                // Simpler approach as a fallback
                $qb = $this->db->getQueryBuilder();
                $qb->select('*')
                    ->from($this->getTableName())
                    ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
                
                // Add individual LIKE conditions for each field
                $searchPattern = '%' . $this->db->escapeLikeParameter($query) . '%';
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('name', $qb->createNamedParameter($searchPattern)),
                        $qb->expr()->like('surname', $qb->createNamedParameter($searchPattern)),
                        $qb->expr()->like('license_number', $qb->createNamedParameter($searchPattern)),
                        $qb->expr()->like('phone_number', $qb->createNamedParameter($searchPattern))
                    )
                );
                
                $qb->orderBy('surname', 'ASC')
                    ->addOrderBy('name', 'ASC');
                
                if ($limit !== null) {
                    $qb->setMaxResults($limit);
                }
                if ($offset !== null) {
                    $qb->setFirstResult($offset);
                }
                
                return $this->findEntities($qb);
                
            } catch (\Exception $e2) {
                // Log the error from the second attempt
                $this->logger->error('Error in fallback search: ' . $e2->getMessage(), [
                    'app' => 'driverlicensemgmt',
                    'exception' => $e2
                ]);
                
                // Return empty array on error
                return [];
            }
        }
    }
}