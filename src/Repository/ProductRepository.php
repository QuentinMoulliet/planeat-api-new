<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Meal;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByHousehold(Household $household): array
    {
        return $this->findBy(['household' => $household], ['normalizedName' => 'ASC']);
    }

    public function findOneByName(Household $household, string $name): ?Product
    {
        return $this->findOneBy(['household' => $household, 'normalizedName' => Product::normalize($name)]);
    }

    /**
     * Number of meals using each product of the household.
     *
     * @return array<int, int> productId => meal count
     */
    public function countMealUsages(Household $household): array
    {
        $rows = $this->getEntityManager()->createQuery(
            'SELECT p.id, COUNT(m.id) AS total FROM ' . Meal::class . ' m JOIN m.products p
             WHERE m.household = :household GROUP BY p.id'
        )->setParameter('household', $household)->getArrayResult();

        return array_column(array_map(fn($r) => [$r['id'], (int) $r['total']], $rows), 1, 0);
    }

    /**
     * @return Meal[] Meals using the given product
     */
    public function findMealsUsing(Product $product): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT m FROM ' . Meal::class . ' m WHERE :product MEMBER OF m.products ORDER BY m.name ASC'
        )->setParameter('product', $product)->getResult();
    }
}
