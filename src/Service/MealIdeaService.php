<?php

namespace App\Service;

use App\Entity\Household;
use App\Entity\Meal;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\MealRepository;
use App\Repository\ProductRepository;
use App\Seed\MealCatalog;
use App\Seed\ProductCatalog;
use function Symfony\Component\String\u;

/**
 * "En manque d'inspi ?": random meal ideas from MealCatalog, in season and not already in the household library.
 * Ideas are only suggestions: the client creates the meal (and any missing product) through the regular endpoints.
 */
class MealIdeaService
{
    public function __construct(
        private MealRepository $mealRepository,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
    ) {}

    /**
     * @param string[] $excludeKeys Ideas already shown (skipped unless the pool is exhausted)
     *
     * @return array{ideas: list<array>, available: int}
     */
    public function suggest(Household $household, \DateTimeImmutable $date, int $count, array $excludeKeys = []): array
    {
        $existingMeals = array_flip(array_map(
            fn(Meal $m) => Product::normalize($m->getName()),
            $this->mealRepository->findBy(['household' => $household]),
        ));
        $season = Meal::seasonOf($date);

        // Pool: in-season ideas the household doesn't have yet
        $pool = [];
        foreach (MealCatalog::MEALS as $entry) {
            [$name, $seasons] = $entry;
            if (($seasons && !in_array($season, $seasons, true)) || isset($existingMeals[Product::normalize($name)])) {
                continue;
            }
            $pool[self::key($name)] = $entry;
        }

        // Prefer ideas not shown yet; start over once they have all been seen
        $candidates = array_diff_key($pool, array_flip($excludeKeys)) ?: $pool;
        $keys = array_keys($candidates);
        shuffle($keys);

        $ideas = [];
        foreach (array_slice($keys, 0, $count) as $key) {
            $ideas[] = $this->present($household, $key, $candidates[$key]);
        }

        return ['ideas' => $ideas, 'available' => count($pool)];
    }

    /**
     * Stable identifier of an idea: "Gigot d'agneau aux flageolets" → "gigot-d-agneau-aux-flageolets".
     */
    public static function key(string $name): string
    {
        return u($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-')->toString();
    }

    /**
     * Products are matched against the household catalogue. A product the household deleted
     * comes back with productId = null and its default aisle, so the client can recreate it.
     */
    private function present(Household $household, string $key, array $entry): array
    {
        [$name, $seasons, $productNames] = $entry;

        $products = [];
        foreach ($productNames as $productName) {
            $product = $this->productRepository->findOneByName($household, $productName);
            $products[] = [
                'productId' => $product?->getId(),
                'name' => $product?->getName() ?? $productName,
                'categoryId' => $product ? $product->getCategory()?->getId() : $this->defaultCategoryId($productName),
            ];
        }

        return [
            'key' => $key,
            'name' => $name,
            'description' => $entry[3] ?? null,
            'seasons' => $seasons,
            'products' => $products,
        ];
    }

    private function defaultCategoryId(string $productName): ?int
    {
        $normalized = Product::normalize($productName);
        foreach (ProductCatalog::PRODUCTS as $categoryName => $names) {
            foreach ($names as $name) {
                if (Product::normalize($name) === $normalized) {
                    return $this->categoryRepository->findOneBy(['name' => $categoryName])?->getId();
                }
            }
        }

        return null;
    }
}
