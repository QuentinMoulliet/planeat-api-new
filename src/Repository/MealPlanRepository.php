<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\MealPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MealPlan>
 */
class MealPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MealPlan::class);
    }

    /**
     * @return MealPlan[] Most recent first
     */
    public function findByHousehold(Household $household): array
    {
        return $this->findBy(['household' => $household], ['startDate' => 'DESC']);
    }

    /**
     * First plan of the household overlapping the given date range, if any.
     */
    public function findOverlapping(Household $household, \DateTimeImmutable $start, \DateTimeImmutable $end): ?MealPlan
    {
        return $this->createQueryBuilder('mp')
            ->where('mp.household = :household')
            ->andWhere('mp.startDate <= :end AND mp.endDate >= :start')
            ->setParameter('household', $household)
            ->setParameter('start', $start, 'date_immutable')
            ->setParameter('end', $end, 'date_immutable')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
