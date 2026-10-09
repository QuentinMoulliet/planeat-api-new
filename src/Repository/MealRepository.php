<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Meal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Meal>
 */
class MealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meal::class);
    }

    /**
     * All meals of the household with their products (single query), sorted by name.
     *
     * @return Meal[]
     */
    public function findByHousehold(Household $household): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('p')
            ->leftJoin('m.products', 'p')
            ->where('m.household = :household')
            ->setParameter('household', $household)
            ->orderBy('m.name', 'ASC')
            ->addOrderBy('p.normalizedName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
