<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Meal;
use App\Entity\MealPlan;
use App\Entity\PlannedMeal;
use App\Entity\Product;
use App\Entity\ShoppingList;
use App\Entity\ShoppingListItem;

/**
 * Converts entities to the JSON arrays returned by the API.
 */
class ApiPresenter
{
    public function category(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'sortOrder' => $category->getSortOrder(),
        ];
    }

    public function product(Product $product, int $mealCount = 0): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'categoryId' => $product->getCategory()?->getId(),
            'isStaple' => $product->isStaple(),
            'mealCount' => $mealCount,
        ];
    }

    public function meal(Meal $meal, ?\DateTimeImmutable $lastPlannedAt = null): array
    {
        $products = $meal->getProducts()->toArray();
        usort($products, fn(Product $a, Product $b) => $a->getNormalizedName() <=> $b->getNormalizedName());

        return [
            'id' => $meal->getId(),
            'name' => $meal->getName(),
            'description' => $meal->getDescription(),
            'seasons' => $meal->getSeasons(),
            'isFavorite' => $meal->isFavorite(),
            'products' => array_map(fn(Product $p) => [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'categoryId' => $p->getCategory()?->getId(),
            ], $products),
            'lastPlannedAt' => $lastPlannedAt?->format('Y-m-d'),
        ];
    }

    public function mealSummary(?Meal $meal): ?array
    {
        return $meal ? [
            'id' => $meal->getId(),
            'name' => $meal->getName(),
            'seasons' => $meal->getSeasons(),
            'isFavorite' => $meal->isFavorite(),
        ] : null;
    }

    public function plannedMeal(PlannedMeal $plannedMeal): array
    {
        return [
            'id' => $plannedMeal->getId(),
            'mealPlanId' => $plannedMeal->getMealPlan()->getId(),
            'date' => $plannedMeal->getDate()->format('Y-m-d'),
            'slot' => $plannedMeal->getSlot(),
            'isLocked' => $plannedMeal->isLocked(),
            'meal' => $this->mealSummary($plannedMeal->getMeal()),
        ];
    }

    public function mealPlan(MealPlan $mealPlan, bool $withSlots = false): array
    {
        $data = [
            'id' => $mealPlan->getId(),
            'startDate' => $mealPlan->getStartDate()->format('Y-m-d'),
            'endDate' => $mealPlan->getEndDate()->format('Y-m-d'),
            'days' => $mealPlan->getDays(),
        ];

        if ($withSlots) {
            $data['plannedMeals'] = array_map($this->plannedMeal(...), $mealPlan->getPlannedMeals()->toArray());
        }

        return $data;
    }

    public function shoppingItem(ShoppingListItem $item): array
    {
        return [
            'id' => $item->getId(),
            'productId' => $item->getProduct()?->getId(),
            'name' => $item->getName(),
            'categoryId' => $item->getCategory()?->getId(),
            'category' => $item->getCategory() ? $this->category($item->getCategory()) : null,
            'quantity' => $item->getQuantity(),
            'isChecked' => $item->isChecked(),
            'isManual' => $item->isManual(),
            'sources' => $item->getSources(),
        ];
    }

    public function shoppingList(?ShoppingList $list): ?array
    {
        if (!$list) {
            return null;
        }

        return [
            'id' => $list->getId(),
            'updatedAt' => $list->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'items' => array_values(array_map($this->shoppingItem(...), $list->getItems()->toArray())),
        ];
    }
}
