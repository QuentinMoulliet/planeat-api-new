<?php

namespace App\Controller\Api;

use App\Entity\MealPlan;
use App\Repository\MealPlanRepository;
use App\Service\ApiPresenter;
use App\Service\MealPlanGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Meal plans: list (history), detail, generation, shuffle, deletion.
 */
#[Route('/api/meal-plans')]
class MealPlanController extends AbstractController
{
    use ApiTrait;

    public function __construct(
        private MealPlanRepository $mealPlanRepository,
        private MealPlanGeneratorService $generator,
        private EntityManagerInterface $entityManager,
        private ApiPresenter $presenter,
    ) {}

    /**
     * All plans (without slots), most recent first.
     */
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(
            fn(MealPlan $plan) => $this->presenter->mealPlan($plan),
            $this->mealPlanRepository->findByHousehold($this->getHousehold()),
        ));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->presenter->mealPlan($this->find($id), true));
    }

    /**
     * Body: startDate (YYYY-MM-DD), days (1 to 31).
     * 409 if the range overlaps an existing plan.
     */
    #[Route('/generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $household = $this->getHousehold();

        $startDate = $this->parseDate($data['startDate'] ?? null);
        if (!$startDate) {
            return $this->fail('Date de début invalide.');
        }

        $days = filter_var($data['days'] ?? null, FILTER_VALIDATE_INT);
        if ($days === false || $days < 1 || $days > MealPlanGeneratorService::MAX_DAYS) {
            return $this->fail('La durée doit être comprise entre 1 et ' . MealPlanGeneratorService::MAX_DAYS . ' jours.');
        }

        $endDate = $startDate->modify('+' . ($days - 1) . ' days');
        $overlap = $this->mealPlanRepository->findOverlapping($household, $startDate, $endDate);
        if ($overlap) {
            return $this->fail(sprintf(
                'Ces dates chevauchent le planning du %s au %s.',
                $overlap->getStartDate()->format('d/m'),
                $overlap->getEndDate()->format('d/m'),
            ), Response::HTTP_CONFLICT);
        }

        $mealPlan = $this->generator->generate($household, $startDate, $days);

        return $this->json($this->presenter->mealPlan($mealPlan, true), Response::HTTP_CREATED);
    }

    /**
     * Re-draw all slots except locked, empty and past ones.
     */
    #[Route('/{id<\d+>}/shuffle', methods: ['POST'])]
    public function shuffle(int $id): JsonResponse
    {
        $mealPlan = $this->find($id);
        $this->generator->shuffle($mealPlan, $this->today());

        return $this->json($this->presenter->mealPlan($mealPlan, true));
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->entityManager->remove($this->find($id));
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function find(int $id): MealPlan
    {
        return $this->owned($this->mealPlanRepository->find($id), fn(MealPlan $p) => $p->getHousehold(), 'Planning introuvable.');
    }
}
