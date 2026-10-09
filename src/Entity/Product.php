<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\String\u;

/**
 * A product of the household catalogue: used both as a meal ingredient and as a shopping list item.
 * Staple products ("toujours en stock": salt, pepper…) are never added automatically to the shopping list.
 * Names are unique per household, case and accent insensitive (see normalizedName).
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCT_HOUSEHOLD_NAME', fields: ['household', 'normalizedName'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Household $household = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $normalizedName = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column]
    private bool $isStaple = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Normalize a product name for duplicate detection: "Crème fraîche " → "creme fraiche".
     */
    public static function normalize(string $name): string
    {
        return u($name)->ascii()->lower()->collapseWhitespace()->trim()->toString();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = u($name)->collapseWhitespace()->trim()->toString();
        $this->normalizedName = self::normalize($name);

        return $this;
    }

    public function getNormalizedName(): ?string
    {
        return $this->normalizedName;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isStaple(): bool
    {
        return $this->isStaple;
    }

    public function setIsStaple(bool $isStaple): static
    {
        $this->isStaple = $isStaple;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
