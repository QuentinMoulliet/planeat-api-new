<?php

namespace App\Controller\Api;

use App\Entity\Meal;
use App\Entity\PlannedMeal;
use App\Repository\MealRepository;
use App\Repository\PlannedMealRepository;
use App\Service\ApiPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Actions on a single planning slot: replace / empty, lock, swap.
 * Past slots stay editable so the history can be corrected.
 */
#[Route('/api/planned-meals')]
class PlannedMealController extends AbstractController
{
    use ApiTrait;

    public function __construct(
        private PlannedMealRepository $plannedMealRepository,
        private MealRepository $mealRepository,
        private EntityManagerInterface $entityManager,
        private ApiPresenter $presenter,
    ) {}

    /**
     * Body: mealId (int, any meal of the household — even out of season) or null to empty the slot.
     */
    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plannedMeal = $this->find($id);
        $data = $this->payload($request);

        if (!array_key_exists('mealId', $data)) {
            return $this->fail('Repas manquant.');
        }

        $meal = null;
        if ($data['mealId'] !== null) {
            $meal = $this->owned($this->mealRepository->find((int) $data['mealId']), fn(Meal $m) => $m->getHousehold(), 'Repas introuvable.');
        }

        $plannedMeal->setMeal($meal);
        $this->entityManager->flush();

        return $this->json($this->presenter->plannedMeal($plannedMeal));
    }

    #[Route('/{id<\d+>}/lock', methods: ['POST'])]
    public function toggleLock(int $id): JsonResponse
    {
        $plannedMeal = $this->find($id);
        $plannedMeal->setIsLocked(!$plannedMeal->isLocked());
        $this->entityManager->flush();

        return $this->json($this->presenter->plannedMeal($plannedMeal));
    }

    /**
     * Body: ids (two slot IDs). Swaps their meals and locks.
     */
    #[Route('/swap', methods: ['POST'])]
    public function swap(Request $request): JsonResponse
    {
        $ids = $this->payload($request)['ids'] ?? null;
        if (!is_array($ids) || count($ids) !== 2 || $ids[0] === $ids[1]) {
            return $this->fail('Sélectionne deux créneaux.');
        }

        [$a, $b] = array_map(fn($id) => $this->find((int) $id), array_values($ids));

        [$mealA, $lockA] = [$a->getMeal(), $a->isLocked()];
        $a->setMeal($b->getMeal())->setIsLocked($b->isLocked());
        $b->setMeal($mealA)->setIsLocked($lockA);
        $this->entityManager->flush();

        return $this->json([$this->presenter->plannedMeal($a), $this->presenter->plannedMeal($b)]);
    }

    private function find(int $id): PlannedMeal
    {
        return $this->owned(
            $this->plannedMealRepository->find($id),
            fn(PlannedMeal $pm) => $pm->getMealPlan()->getHousehold(),
            'Créneau introuvable.',
        );
    }
}
