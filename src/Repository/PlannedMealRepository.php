<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\MealPlan;
use App\Entity\PlannedMeal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlannedMeal>
 */
class PlannedMealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlannedMeal::class);
    }

    /**
     * Last date each meal was planned, strictly before (or up to, if $inclusive) the given date.
     * This is the "last eaten" date used by the scoring: there is no separate history.
     *
     * @return array<int, \DateTimeImmutable> mealId => last planned date
     */
    public function findLastPlannedDates(Household $household, \DateTimeImmutable $before, bool $inclusive = false): array
    {
        $rows = $this->createQueryBuilder('pm')
            ->select('IDENTITY(pm.meal) AS mealId', 'MAX(pm.date) AS lastDate')
            ->join('pm.mealPlan', 'mp')
            ->where('mp.household = :household')
            ->andWhere('pm.meal IS NOT NULL')
            ->andWhere($inclusive ? 'pm.date <= :before' : 'pm.date < :before')
            ->setParameter('household', $household)
            ->setParameter('before', $before, 'date_immutable')
            ->groupBy('pm.meal')
            ->getQuery()
            ->getArrayResult();

        $dates = [];
        foreach ($rows as $row) {
            $dates[(int) $row['mealId']] = new \DateTimeImmutable($row['lastDate']);
        }

        return $dates;
    }

    /**
     * Non-empty slots of the given plans from a date on, with meals and products loaded.
     *
     * @param MealPlan[] $mealPlans
     *
     * @return PlannedMeal[]
     */
    public function findFilledFrom(array $mealPlans, \DateTimeImmutable $from): array
    {
        return $this->createQueryBuilder('pm')
            ->addSelect('m', 'p')
            ->join('pm.meal', 'm')
            ->leftJoin('m.products', 'p')
            ->where('pm.mealPlan IN (:plans)')
            ->andWhere('pm.date >= :from')
            ->setParameter('plans', $mealPlans)
            ->setParameter('from', $from, 'date_immutable')
            ->orderBy('pm.date', 'ASC')
            ->addOrderBy('pm.slot', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * All slots of the household between two dates (inclusive).
     *
     * @return PlannedMeal[]
     */
    public function findBetween(Household $household, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('pm')
            ->addSelect('m')
            ->join('pm.mealPlan', 'mp')
            ->leftJoin('pm.meal', 'm')
            ->where('mp.household = :household')
            ->andWhere('pm.date BETWEEN :from AND :to')
            ->setParameter('household', $household)
            ->setParameter('from', $from, 'date_immutable')
            ->setParameter('to', $to, 'date_immutable')
            ->orderBy('pm.date', 'ASC')
            ->addOrderBy('pm.slot', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
