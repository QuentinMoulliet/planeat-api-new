<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Household;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Seed\ProductCatalog;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Copies the default product catalogue (ProductCatalog) into a household.
 * Only adds products whose normalized name doesn't exist yet in the household.
 */
class ProductCatalogSeeder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
    ) {}

    /**
     * @return int Number of products created
     *
     * @throws \RuntimeException If a catalogue category doesn't exist (app:seed-categories not run)
     */
    public function seed(Household $household): int
    {
        $categories = [];
        foreach ($this->categoryRepository->findAll() as $category) {
            $categories[$category->getName()] = $category;
        }

        $existing = [];
        foreach ($this->productRepository->findBy(['household' => $household]) as $product) {
            $existing[$product->getNormalizedName()] = true;
        }

        $staples = array_map(Product::normalize(...), ProductCatalog::STAPLES);
        $created = 0;

        foreach (ProductCatalog::PRODUCTS as $categoryName => $names) {
            $category = $categories[$categoryName] ?? throw new \RuntimeException(
                "Category \"$categoryName\" not found. Run app:seed-categories first."
            );

            foreach ($names as $name) {
                $normalized = Product::normalize($name);
                if (isset($existing[$normalized])) {
                    continue;
                }

                $product = (new Product())
                    ->setHousehold($household)
                    ->setName($name)
                    ->setCategory($category)
                    ->setIsStaple(in_array($normalized, $staples, true));

                $this->entityManager->persist($product);
                $existing[$normalized] = true;
                $created++;
            }
        }

        $this->entityManager->flush();

        return $created;
    }
}
