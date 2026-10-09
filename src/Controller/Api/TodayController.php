<?php

namespace App\Controller\Api;

use App\Entity\PlannedMeal;
use App\Repository\PlannedMealRepository;
use App\Repository\ShoppingListRepository;
use App\Service\ApiPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Home screen data: today's and tomorrow's slots + shopping list progress.
 */
class TodayController extends AbstractController
{
    use ApiTrait;

    #[Route('/api/today', methods: ['GET'])]
    public function index(
        PlannedMealRepository $plannedMealRepository,
        ShoppingListRepository $shoppingListRepository,
        ApiPresenter $presenter,
    ): JsonResponse {
        $household = $this->getHousehold();
        $today = $this->today();
        $tomorrow = $today->modify('+1 day');

        $days = [];
        foreach ([$today, $tomorrow] as $date) {
            $days[$date->format('Y-m-d')] = ['date' => $date->format('Y-m-d'), 'lunch' => null, 'dinner' => null];
        }
        foreach ($plannedMealRepository->findBetween($household, $today, $tomorrow) as $plannedMeal) {
            $days[$plannedMeal->getDate()->format('Y-m-d')][$plannedMeal->getSlot()] = $presenter->plannedMeal($plannedMeal);
        }

        $list = $shoppingListRepository->findActive($household);
        $items = $list ? $list->getItems()->toArray() : [];

        return $this->json([
            'days' => array_values($days),
            'shopping' => [
                'total' => count($items),
                'remaining' => count(array_filter($items, fn($i) => !$i->isChecked())),
            ],
        ]);
    }
}
