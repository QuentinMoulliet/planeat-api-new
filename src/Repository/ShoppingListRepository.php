<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\ShoppingList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingList>
 */
class ShoppingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingList::class);
    }

    /**
     * The household's active list with its items, products and categories loaded.
     */
    public function findActive(Household $household): ?ShoppingList
    {
        return $this->createQueryBuilder('sl')
            ->addSelect('i', 'p', 'pc', 'c')
            ->leftJoin('sl.items', 'i')
            ->leftJoin('i.product', 'p')
            ->leftJoin('p.category', 'pc')
            ->leftJoin('i.category', 'c')
            ->where('sl.household = :household')
            ->setParameter('household', $household)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
