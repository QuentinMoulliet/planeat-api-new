<?php

namespace App\Controller\Api;

use App\Service\MealIdeaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Meal ideas for when inspiration runs out (free, built-in catalogue — see MealCatalog).
 */
class MealIdeaController extends AbstractController
{
    use ApiTrait;

    /**
     * Query: count (1 to 10, default 5), exclude (comma-separated keys already shown).
     */
    #[Route('/api/meal-ideas', methods: ['GET'])]
    public function index(Request $request, MealIdeaService $ideas): JsonResponse
    {
        $count = min(10, max(1, $request->query->getInt('count', 5)));
        $exclude = array_filter(explode(',', (string) $request->query->get('exclude', '')));

        return $this->json($ideas->suggest($this->getHousehold(), $this->today(), $count, $exclude));
    }
}
