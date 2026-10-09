<?php

namespace App\Service;

use App\Entity\Household;
use App\Entity\MealPlan;
use App\Entity\ShoppingList;
use App\Entity\ShoppingListItem;
use App\Repository\PlannedMealRepository;
use App\Repository\ShoppingListRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Builds the household's shopping list from one or several meal plans.
 *
 * Every non-empty slot from today on counts: quantity of a product = number of slots whose meal
 * contains it (Lasagnes + Bolognaise → Viande hachée ×2). Staple products are skipped.
 *
 * On regeneration:
 * - manual items are kept untouched (a product already added manually is not duplicated)
 * - generated items are recomputed (a checked item stays checked), obsolete ones removed
 */
class ShoppingListGeneratorService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShoppingListRepository $shoppingListRepository,
        private PlannedMealRepository $plannedMealRepository,
    ) {}

    /**
     * @param MealPlan[] $mealPlans
     */
    public function generate(Household $household, array $mealPlans, \DateTimeImmutable $today): ShoppingList
    {
        // productId => ['product' => Product, 'count' => int, 'sources' => string[]]
        $needs = [];
        foreach ($this->plannedMealRepository->findFilledFrom($mealPlans, $today) as $plannedMeal) {
            foreach ($plannedMeal->getMeal()->getProducts() as $product) {
                if ($product->isStaple()) {
                    continue;
                }
                $needs[$product->getId()] ??= ['product' => $product, 'count' => 0, 'sources' => []];
                $needs[$product->getId()]['count']++;
                $needs[$product->getId()]['sources'][] = $plannedMeal->getMeal()->getName();
            }
        }

        $list = $this->shoppingListRepository->findActive($household);
        if (!$list) {
            $list = (new ShoppingList())->setHousehold($household);
            $this->entityManager->persist($list);
        }

        // Index existing product lines; drop generated lines that are no longer needed
        $existing = [];
        foreach ($list->getItems()->toArray() as $item) {
            $productId = $item->getProduct()?->getId();
            if ($productId === null) {
                continue;
            }
            if (!$item->isManual() && !isset($needs[$productId])) {
                $list->removeItem($item);
                continue;
            }
            $existing[$productId] = $item;
        }

        foreach ($needs as $productId => $need) {
            $item = $existing[$productId] ?? null;

            if ($item?->isManual()) {
                continue;
            }
            if (!$item) {
                $item = (new ShoppingListItem())->setProduct($need['product']);
                $list->addItem($item);
            }

            $item->setQuantity($need['count'])->setSources($this->formatSources($need['sources']));
        }

        $list->touch();
        $this->entityManager->flush();

        return $list;
    }

    /**
     * ["Lasagnes", "Bolognaise", "Lasagnes"] → ["Lasagnes ×2", "Bolognaise"]
     *
     * @param string[] $mealNames
     *
     * @return string[]
     */
    private function formatSources(array $mealNames): array
    {
        $sources = [];
        foreach (array_count_values($mealNames) as $name => $count) {
            $sources[] = $count > 1 ? "$name ×$count" : (string) $name;
        }

        return $sources;
    }
}
