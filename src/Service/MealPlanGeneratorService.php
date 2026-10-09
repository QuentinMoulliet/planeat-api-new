<?php

namespace App\Service;

use App\Entity\Household;
use App\Entity\Meal;
use App\Entity\MealPlan;
use App\Entity\PlannedMeal;
use App\Repository\MealRepository;
use App\Repository\PlannedMealRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generates and reshuffles meal plans (lunch + dinner every day).
 *
 * Each slot gets the best scored in-season meal, the score being computed for the slot's date:
 * - Variety: days between the slot and the closest other placement of the meal
 *   (last planned date before the plan, or another slot of this plan), capped at 30. Never planned = 30.
 * - +5 if favorite
 * - +0 to 15 random, drawn for each slot, so that two generations / shuffles give different results
 *
 * Distribution rules:
 * 1. A meal is not repeated within the same week of the plan
 * 2. A meal is never placed on two consecutive slots
 * When there aren't enough meals, rule 1 is relaxed first (the least used meals of the plan
 * being picked first, to keep repeats balanced), then rule 2 as a last resort.
 * Out-of-season meals are never placed automatically: with no in-season meal, the slot stays empty.
 */
class MealPlanGeneratorService
{
    public const MAX_DAYS = 31;
    private const VARIETY_CAP = 30;
    private const FAVORITE_BONUS = 5;
    private const RANDOM_MAX = 15;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MealRepository $mealRepository,
        private PlannedMealRepository $plannedMealRepository,
    ) {}

    /**
     * Create a plan of $days days starting at $startDate, with every slot filled.
     */
    public function generate(Household $household, \DateTimeImmutable $startDate, int $days): MealPlan
    {
        $mealPlan = (new MealPlan())
            ->setHousehold($household)
            ->setStartDate($startDate)
            ->setEndDate($startDate->modify('+' . ($days - 1) . ' days'));

        $slots = [];
        for ($day = 0; $day < $days; $day++) {
            foreach (PlannedMeal::SLOTS as $slot) {
                $plannedMeal = (new PlannedMeal())
                    ->setDate($startDate->modify("+$day days"))
                    ->setSlot($slot);
                $mealPlan->addPlannedMeal($plannedMeal);
                $slots[] = $plannedMeal;
            }
        }

        $this->fill($mealPlan, $slots, array_keys($slots));

        $this->entityManager->persist($mealPlan);
        $this->entityManager->flush();

        return $mealPlan;
    }

    /**
     * Re-draw every slot of the plan except locked ones, empty ones (emptied on purpose)
     * and past ones (they are the history).
     */
    public function shuffle(MealPlan $mealPlan, \DateTimeImmutable $today): void
    {
        $slots = $mealPlan->getSortedPlannedMeals();

        $toFill = [];
        foreach ($slots as $index => $slot) {
            if (!$slot->isLocked() && $slot->getMeal() !== null && $slot->getDate() >= $today) {
                $toFill[] = $index;
            }
        }

        $this->fill($mealPlan, $slots, $toFill);
        $this->entityManager->flush();
    }

    /**
     * @param PlannedMeal[] $slots  All slots of the plan, chronologically ordered
     * @param int[]         $toFill Indexes of the slots to (re)assign — the others are fixed
     */
    private function fill(MealPlan $mealPlan, array $slots, array $toFill): void
    {
        $meals = $this->mealRepository->findBy(['household' => $mealPlan->getHousehold()]);
        $toFill = array_flip($toFill);
        $startDate = $mealPlan->getStartDate();

        // Known placements: history before the plan + fixed slots of the plan
        $placements = [];
        $usesInPlan = [];
        foreach ($this->plannedMealRepository->findLastPlannedDates($mealPlan->getHousehold(), $startDate) as $mealId => $date) {
            $placements[$mealId][] = $date;
        }
        $usedByWeek = [];
        foreach ($slots as $index => $slot) {
            if (!isset($toFill[$index]) && $slot->getMeal() !== null) {
                $mealId = $slot->getMeal()->getId();
                $placements[$mealId][] = $slot->getDate();
                $usesInPlan[$mealId] = ($usesInPlan[$mealId] ?? 0) + 1;
                $usedByWeek[$this->weekOf($startDate, $slot->getDate())][$mealId] = true;
            }
        }

        foreach ($slots as $index => $slot) {
            if (!isset($toFill[$index])) {
                continue;
            }

            $date = $slot->getDate();
            $week = $this->weekOf($startDate, $date);

            $candidates = array_filter($meals, fn(Meal $meal) => $meal->isInSeason($date));
            if (!$candidates) {
                $slot->setMeal(null);
                continue;
            }

            $scores = [];
            foreach ($candidates as $meal) {
                $scores[$meal->getId()] = $this->score($meal, $date, $placements);
            }
            usort($candidates, fn(Meal $a, Meal $b) => $scores[$b->getId()] <=> $scores[$a->getId()]);

            // Same candidates, least used in the plan first (for the relaxed passes)
            $leastUsed = $candidates;
            usort($leastUsed, fn(Meal $a, Meal $b) => ($usesInPlan[$a->getId()] ?? 0) <=> ($usesInPlan[$b->getId()] ?? 0)
                ?: $scores[$b->getId()] <=> $scores[$a->getId()]);

            // Neighbours: previous slot is already decided; next slot only counts if fixed
            $previousId = isset($slots[$index - 1]) ? $slots[$index - 1]->getMeal()?->getId() : null;
            $nextId = isset($slots[$index + 1]) && !isset($toFill[$index + 1]) ? $slots[$index + 1]->getMeal()?->getId() : null;
            $isNeighbour = fn(Meal $meal) => in_array($meal->getId(), [$previousId, $nextId], true);
            $usedThisWeek = fn(Meal $meal) => isset($usedByWeek[$week][$meal->getId()]);

            $picked = $this->firstMatching($candidates, fn(Meal $m) => !$usedThisWeek($m) && !$isNeighbour($m))
                ?? $this->firstMatching($leastUsed, fn(Meal $m) => !$isNeighbour($m))
                ?? $leastUsed[0];

            $slot->setMeal($picked);
            $placements[$picked->getId()][] = $date;
            $usesInPlan[$picked->getId()] = ($usesInPlan[$picked->getId()] ?? 0) + 1;
            $usedByWeek[$week][$picked->getId()] = true;
        }
    }

    /**
     * @param array<int, \DateTimeImmutable[]> $placements
     */
    private function score(Meal $meal, \DateTimeImmutable $date, array $placements): float
    {
        $variety = self::VARIETY_CAP;
        foreach ($placements[$meal->getId()] ?? [] as $placedAt) {
            $variety = min($variety, (int) $placedAt->diff($date)->days);
        }

        return $variety
            + ($meal->isFavorite() ? self::FAVORITE_BONUS : 0)
            + mt_rand(0, self::RANDOM_MAX * 10) / 10;
    }

    /**
     * @param Meal[] $meals
     */
    private function firstMatching(array $meals, callable $predicate): ?Meal
    {
        foreach ($meals as $meal) {
            if ($predicate($meal)) {
                return $meal;
            }
        }

        return null;
    }

    private function weekOf(\DateTimeImmutable $startDate, \DateTimeImmutable $date): int
    {
        return intdiv((int) $startDate->diff($date)->days, 7);
    }
}
