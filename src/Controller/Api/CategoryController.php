<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use App\Service\ApiPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Global supermarket aisles (read only — managed by app:seed-categories).
 */
#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(CategoryRepository $repository, ApiPresenter $presenter): JsonResponse
    {
        return $this->json(array_map(
            $presenter->category(...),
            $repository->findBy([], ['sortOrder' => 'ASC']),
        ));
    }
}
