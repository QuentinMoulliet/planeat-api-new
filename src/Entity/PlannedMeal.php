<?php

namespace App\Entity;

use App\Repository\PlannedMealRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One slot of a meal plan (a date + lunch or dinner).
 * A null meal means an empty slot (restaurant, leftovers, guests…): ignored by the shopping list.
 */
#[ORM\Entity(repositoryClass: PlannedMealRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PLANNED_MEAL_SLOT', fields: ['mealPlan', 'date', 'slot'])]
class PlannedMeal
{
    public const LUNCH = 'lunch';
    public const DINNER = 'dinner';
    public const SLOTS = [self::LUNCH, self::DINNER];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'plannedMeals')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MealPlan $mealPlan = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 10)]
    private ?string $slot = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Meal $meal = null;

    #[ORM\Column]
    private bool $isLocked = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMealPlan(): ?MealPlan
    {
        return $this->mealPlan;
    }

    public function setMealPlan(MealPlan $mealPlan): static
    {
        $this->mealPlan = $mealPlan;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getSlot(): ?string
    {
        return $this->slot;
    }

    public function setSlot(string $slot): static
    {
        $this->slot = $slot;

        return $this;
    }

    public function getMeal(): ?Meal
    {
        return $this->meal;
    }

    public function setMeal(?Meal $meal): static
    {
        $this->meal = $meal;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): static
    {
        $this->isLocked = $isLocked;

        return $this;
    }
}
