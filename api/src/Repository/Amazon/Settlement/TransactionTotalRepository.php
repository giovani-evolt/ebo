<?php

namespace App\Repository\Amazon\Settlement;

use App\Entity\Amazon\Settlement\TransactionTotal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransactionTotal>
 */
class TransactionTotalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionTotal::class);
    }

   /**
    * @return TransactionTotal[] Returns an array of TransactionTotal objects
    */
   public function getSummaryByYearMonth($year, $month): array
   {
        $maxYear = $year;
        $maxMonth = $month;

        $qb = $this->createQueryBuilder('t')
            ->select('t.year, t.month, t.totalType')
            ->addSelect('SUM(t.totalAmount) as totalAmount')
            ->groupBy('t.year')
            ->addGroupBy('t.month')
            ->addGroupBy('t.totalType');

        if(empty($year) || empty($month)){
            $subQuery = $this->createQueryBuilder('t2')
            ->select('MAX(t2.year) as max_year, MAX(t2.month) as max_month')
            ->getQuery()
            ->getSingleResult();
    
            $maxYear = $subQuery['max_year'];
            $maxMonth = $subQuery['max_month'];

            $qb
                ->where('t.year >= :year')
                ->setParameter('year', $maxYear)
            ;

            if($maxMonth < 12){
              $qb
                    ->orWhere('t.year >= :year2 AND t.month >= :month')
                    ->setParameter('year2', $maxYear - 1)
                    ->setParameter('month', $maxMonth)
                ;
            }
        } else {
            $qb->andWhere('t.year = :year')
                ->andWhere('t.month = :month')
                ->setParameter('year', $maxYear)
                ->setParameter('month', $maxMonth);
        }

        return $qb
            ->orderBy('t.year', 'DESC')
            ->orderBy('t.month', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getSummaryByLastMonths($months = 6): array
   {
        $subQuery = $this->createQueryBuilder('t2')
            ->select('MAX(t2.year) as max_year, MAX(t2.month) as max_month')
            ->getQuery()
            ->getSingleResult();

        $maxYear = $subQuery['max_year'];
        $maxMonth = $subQuery['max_month'];

        

        return $this->createQueryBuilder('t')
           ->select('t.year, t.month, t.totalType')
           ->addSelect('SUM(t.totalAmount) as totalAmount')
           ->andWhere('t.year >= :year')
           ->andWhere('t.month >= :month')
           ->setParameter('year', $maxYear)
           ->setParameter('month', $maxMonth)
           ->groupBy('t.year')
           ->addGroupBy('t.month')
           ->addGroupBy('t.totalType')
           ->getQuery()
           ->getResult()
       ;
    }
}
