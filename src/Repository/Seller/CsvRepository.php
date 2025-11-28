<?php

namespace App\Repository\Seller;

use App\Entity\Seller\Csv;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Csv>
 */
class CsvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Csv::class);
    }

    //    /**
    //     * @return Csv[] Returns an array of Csv objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function findAllCsvsByStatus($status, $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function loadToWorkBatch(int $limit = 1): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $wip = CSV::STATUS_WIP;
        $pending = CSV::STATUS_PENDING;

        $sql = <<<SQL
            UPDATE csv AS c
            SET status = $wip
            FROM (
                SELECT id
                FROM csv
                WHERE status = $pending
                ORDER BY id
                FOR UPDATE SKIP LOCKED
                LIMIT :limit
            ) AS sel
            WHERE c.id = sel.id
            RETURNING c.id;
        SQL;

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('limit', $limit);
        $result = $stmt->executeQuery();

        $ids = $result->fetchFirstColumn(); // Devuelve una lista de IDs

        if (!$ids) {
            return [];
        }

        // Devolver las entidades completas
        return $this->findBy(['id' => $ids]);
    }

}
