<?php

namespace App\Entity;

use App\Repository\MealPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A meal plan covers a date range (inclusive), with a lunch and a dinner slot per day.
 * Plans of the same household never overlap.
 */
#[ORM\Entity(repositoryClass: MealPlanRepository::class)]
class MealPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Household $household = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $endDate = null;

    /** @var Collection<int, PlannedMeal> */
    #[ORM\OneToMany(targetEntity: PlannedMeal::class, mappedBy: 'mealPlan', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $plannedMeals;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->plannedMeals = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(Household $household): static
    {
        $this->household = $household;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }

    /**
     * @return Collection<int, PlannedMeal>
     */
    public function getPlannedMeals(): Collection
    {
        return $this->plannedMeals;
    }

    /**
     * @return PlannedMeal[] Chronological order, lunch before dinner
     */
    public function getSortedPlannedMeals(): array
    {
        $slots = $this->plannedMeals->toArray();
        usort($slots, fn(PlannedMeal $a, PlannedMeal $b) => [$a->getDate(), array_search($a->getSlot(), PlannedMeal::SLOTS)]
            <=> [$b->getDate(), array_search($b->getSlot(), PlannedMeal::SLOTS)]);

        return $slots;
    }

    public function addPlannedMeal(PlannedMeal $plannedMeal): static
    {
        if (!$this->plannedMeals->contains($plannedMeal)) {
            $this->plannedMeals->add($plannedMeal);
            $plannedMeal->setMealPlan($this);
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
