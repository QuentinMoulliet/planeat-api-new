<?php

namespace App\Controller\Api;

use App\Entity\Meal;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ApiPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Household product catalogue (meal ingredients and shopping list items).
 */
#[Route('/api/products')]
class ProductController extends AbstractController
{
    use ApiTrait;

    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ApiPresenter $presenter,
    ) {}

    /**
     * All products of the household, with the number of meals using each one.
     */
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $household = $this->getHousehold();
        $usages = $this->productRepository->countMealUsages($household);

        return $this->json(array_map(
            fn(Product $p) => $this->presenter->product($p, $usages[$p->getId()] ?? 0),
            $this->productRepository->findByHousehold($household),
        ));
    }

    /**
     * Body: name (required), categoryId (nullable), isStaple (bool).
     * 409 with the existing product if the name is already used.
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $product = (new Product())->setHousehold($this->getHousehold());

        return $this->save($product, $this->payload($request), Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->save($this->find($id), $this->payload($request), Response::HTTP_OK);
    }

    /**
     * Deletion is refused (409) while the product is used by a meal.
     */
    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->find($id);

        $meals = $this->productRepository->findMealsUsing($product);
        if ($meals) {
            return $this->fail(
                'Ce produit est utilisé dans ' . count($meals) . ' repas.',
                Response::HTTP_CONFLICT,
                ['meals' => array_map(fn(Meal $m) => ['id' => $m->getId(), 'name' => $m->getName()], $meals)],
            );
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function save(Product $product, array $data, int $status): JsonResponse
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->fail('Le nom est obligatoire (100 caractères max).');
        }

        $duplicate = $this->productRepository->findOneByName($this->getHousehold(), $name);
        if ($duplicate && $duplicate !== $product) {
            return $this->fail('Ce produit existe déjà.', Response::HTTP_CONFLICT, [
                'product' => $this->presenter->product($duplicate),
            ]);
        }

        $category = null;
        if (!empty($data['categoryId'])) {
            $category = $this->categoryRepository->find($data['categoryId']);
            if (!$category) {
                return $this->fail('Rayon introuvable.');
            }
        }

        $product
            ->setName($name)
            ->setCategory($category)
            ->setIsStaple((bool) ($data['isStaple'] ?? false));

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $usages = $this->productRepository->countMealUsages($this->getHousehold());

        return $this->json($this->presenter->product($product, $usages[$product->getId()] ?? 0), $status);
    }

    private function find(int $id): Product
    {
        return $this->owned($this->productRepository->find($id), fn(Product $p) => $p->getHousehold(), 'Produit introuvable.');
    }
}
