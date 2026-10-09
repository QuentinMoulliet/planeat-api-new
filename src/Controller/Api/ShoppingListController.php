<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ShoppingList;
use App\Entity\ShoppingListItem;
use App\Repository\CategoryRepository;
use App\Repository\MealPlanRepository;
use App\Repository\ProductRepository;
use App\Repository\ShoppingListItemRepository;
use App\Repository\ShoppingListRepository;
use App\Service\ApiPresenter;
use App\Service\ShoppingListGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The household's single active shopping list.
 * Every write endpoint returns the whole list, so clients always stay in sync.
 */
#[Route('/api/shopping-list')]
class ShoppingListController extends AbstractController
{
    use ApiTrait;

    public function __construct(
        private ShoppingListRepository $shoppingListRepository,
        private ShoppingListItemRepository $itemRepository,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ApiPresenter $presenter,
    ) {}

    /**
     * The active list, or null when there is none.
     */
    #[Route('', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return $this->respond();
    }

    /**
     * Body: mealPlanIds (int[]). Generates or regenerates the list from the slots from today on.
     */
    #[Route('/generate', methods: ['POST'])]
    public function generate(
        Request $request,
        MealPlanRepository $mealPlanRepository,
        ShoppingListGeneratorService $generator,
    ): JsonResponse {
        $ids = array_unique(array_map('intval', (array) ($this->payload($request)['mealPlanIds'] ?? [])));
        $plans = $ids ? $mealPlanRepository->findBy(['id' => $ids, 'household' => $this->getHousehold()]) : [];

        if (!$plans || count($plans) !== count($ids)) {
            return $this->fail('Sélectionne au moins un planning.');
        }

        $generator->generate($this->getHousehold(), $plans, $this->today());

        return $this->respond();
    }

    /**
     * Delete the whole list.
     */
    #[Route('', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        $list = $this->shoppingListRepository->findActive($this->getHousehold());
        if ($list) {
            $this->entityManager->remove($list);
            $this->entityManager->flush();
        }

        return $this->json(null);
    }

    #[Route('/clear-checked', methods: ['POST'])]
    public function clearChecked(): JsonResponse
    {
        $list = $this->shoppingListRepository->findActive($this->getHousehold());
        if ($list) {
            foreach ($list->getItems()->toArray() as $item) {
                if ($item->isChecked()) {
                    $list->removeItem($item);
                }
            }
            $list->touch();
            $this->entityManager->flush();
        }

        return $this->respond();
    }

    /**
     * Body: productId — or customName + categoryId (one-off item) —, quantity (default 1).
     * Adding something already on the list increases its quantity.
     */
    #[Route('/items', methods: ['POST'])]
    public function addItem(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $household = $this->getHousehold();
        $quantity = max(1, (int) ($data['quantity'] ?? 1));

        $list = $this->shoppingListRepository->findActive($household);
        if (!$list) {
            $list = (new ShoppingList())->setHousehold($household);
            $this->entityManager->persist($list);
        }

        $product = null;
        if (!empty($data['productId'])) {
            $product = $this->owned($this->productRepository->find((int) $data['productId']), fn(Product $p) => $p->getHousehold(), 'Produit introuvable.');
            $existing = $list->getItems()->findFirst(fn($k, ShoppingListItem $i) => $i->getProduct() === $product);
        } else {
            $name = trim((string) ($data['customName'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                return $this->fail('Le nom est obligatoire (100 caractères max).');
            }
            $category = !empty($data['categoryId']) ? $this->categoryRepository->find((int) $data['categoryId']) : null;
            $existing = $list->getItems()->findFirst(fn($k, ShoppingListItem $i) => $i->getProduct() === null
                && Product::normalize((string) $i->getCustomName()) === Product::normalize($name));
        }

        if ($existing) {
            $existing->setQuantity($existing->getQuantity() + $quantity)->setIsChecked(false);
        } else {
            $item = (new ShoppingListItem())->setQuantity($quantity)->setIsManual(true);
            $product ? $item->setProduct($product) : $item->setCustomName($name)->setCategory($category);
            $list->addItem($item);
        }

        $list->touch();
        $this->entityManager->flush();

        return $this->respond(Response::HTTP_CREATED);
    }

    /**
     * Body: quantity (int ≥ 1) and/or isChecked (bool).
     */
    #[Route('/items/{id<\d+>}', methods: ['PUT'])]
    public function updateItem(int $id, Request $request): JsonResponse
    {
        $item = $this->findItem($id);
        $data = $this->payload($request);

        if (array_key_exists('quantity', $data)) {
            $quantity = filter_var($data['quantity'], FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 1) {
                return $this->fail('Quantité invalide.');
            }
            $item->setQuantity($quantity);
        }
        if (array_key_exists('isChecked', $data)) {
            $item->setIsChecked((bool) $data['isChecked']);
        }

        $item->getShoppingList()->touch();
        $this->entityManager->flush();

        return $this->respond();
    }

    #[Route('/items/{id<\d+>}', methods: ['DELETE'])]
    public function deleteItem(int $id): JsonResponse
    {
        $item = $this->findItem($id);
        $item->getShoppingList()->removeItem($item)->touch();
        $this->entityManager->flush();

        return $this->respond();
    }

    private function respond(int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json(
            $this->presenter->shoppingList($this->shoppingListRepository->findActive($this->getHousehold())),
            $status,
        );
    }

    private function findItem(int $id): ShoppingListItem
    {
        return $this->owned(
            $this->itemRepository->find($id),
            fn(ShoppingListItem $i) => $i->getShoppingList()->getHousehold(),
            'Article introuvable.',
        );
    }
}
