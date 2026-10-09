<?php

namespace App\Controller\Api;

use App\Entity\Meal;
use App\Repository\MealRepository;
use App\Repository\PlannedMealRepository;
use App\Repository\ProductRepository;
use App\Service\ApiPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Household meal library.
 */
#[Route('/api/meals')]
class MealController extends AbstractController
{
    use ApiTrait;

    public function __construct(
        private MealRepository $mealRepository,
        private ProductRepository $productRepository,
        private PlannedMealRepository $plannedMealRepository,
        private EntityManagerInterface $entityManager,
        private ApiPresenter $presenter,
    ) {}

    /**
     * All meals with their products and last planned date (up to today).
     */
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $household = $this->getHousehold();
        $lastDates = $this->plannedMealRepository->findLastPlannedDates($household, $this->today(), true);

        return $this->json(array_map(
            fn(Meal $m) => $this->presenter->meal($m, $lastDates[$m->getId()] ?? null),
            $this->mealRepository->findByHousehold($household),
        ));
    }

    /**
     * Body: name (required), description, seasons (string[]), isFavorite, productIds (int[], at least one).
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $meal = (new Meal())->setHousehold($this->getHousehold());

        return $this->save($meal, $this->payload($request), Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->save($this->find($id), $this->payload($request), Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}/favorite', methods: ['POST'])]
    public function toggleFavorite(int $id): JsonResponse
    {
        $meal = $this->find($id);
        $meal->setIsFavorite(!$meal->isFavorite());
        $this->entityManager->flush();

        return $this->json($this->present($meal));
    }

    /**
     * Planned slots using this meal become empty (FK ON DELETE SET NULL).
     */
    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->entityManager->remove($this->find($id));
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function save(Meal $meal, array $data, int $status): JsonResponse
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            return $this->fail('Le nom est obligatoire (150 caractères max).');
        }

        $seasons = $data['seasons'] ?? [];
        if (!is_array($seasons) || array_diff($seasons, Meal::SEASONS)) {
            return $this->fail('Saison invalide.');
        }

        $productIds = array_unique(array_map('intval', (array) ($data['productIds'] ?? [])));
        $products = $productIds ? $this->productRepository->findBy([
            'id' => $productIds,
            'household' => $this->getHousehold(),
        ]) : [];
        if (!$products) {
            return $this->fail('Ajoute au moins un ingrédient.');
        }
        if (count($products) !== count($productIds)) {
            return $this->fail('Ingrédient introuvable.');
        }

        $meal
            ->setName($name)
            ->setDescription(isset($data['description']) ? (string) $data['description'] : null)
            ->setSeasons($seasons)
            ->setIsFavorite((bool) ($data['isFavorite'] ?? false))
            ->setProducts($products);

        $this->entityManager->persist($meal);
        $this->entityManager->flush();

        return $this->json($this->present($meal), $status);
    }

    private function present(Meal $meal): array
    {
        $lastDates = $this->plannedMealRepository->findLastPlannedDates($this->getHousehold(), $this->today(), true);

        return $this->presenter->meal($meal, $lastDates[$meal->getId()] ?? null);
    }

    private function find(int $id): Meal
    {
        return $this->owned($this->mealRepository->find($id), fn(Meal $m) => $m->getHousehold(), 'Repas introuvable.');
    }
}
