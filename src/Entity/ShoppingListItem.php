<?php

namespace App\Entity;

use App\Repository\ShoppingListItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A shopping list line: either a catalogue product or a one-off free text item (customName).
 * Generated items (isManual = false) are recomputed on each regeneration; manual items are kept.
 */
#[ORM\Entity(repositoryClass: ShoppingListItemRepository::class)]
class ShoppingListItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ShoppingList $shoppingList = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $customName = null;

    /** Only used for free text items — product items use their product's category */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private bool $isChecked = false;

    #[ORM\Column]
    private bool $isManual = false;

    /** @var list<string>|null Names of the planned meals that need this product (generated items only) */
    #[ORM\Column(nullable: true)]
    private ?array $sources = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShoppingList(): ?ShoppingList
    {
        return $this->shoppingList;
    }

    public function setShoppingList(ShoppingList $shoppingList): static
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(?string $customName): static
    {
        $this->customName = $customName !== null ? trim($customName) : null;

        return $this;
    }

    public function getName(): string
    {
        return $this->product?->getName() ?? (string) $this->customName;
    }

    public function getCategory(): ?Category
    {
        return $this->product ? $this->product->getCategory() : $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }

    public function isChecked(): bool
    {
        return $this->isChecked;
    }

    public function setIsChecked(bool $isChecked): static
    {
        $this->isChecked = $isChecked;

        return $this;
    }

    public function isManual(): bool
    {
        return $this->isManual;
    }

    public function setIsManual(bool $isManual): static
    {
        $this->isManual = $isManual;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getSources(): array
    {
        return $this->sources ?? [];
    }

    /**
     * @param list<string> $sources
     */
    public function setSources(array $sources): static
    {
        $this->sources = $sources ?: null;

        return $this;
    }
}
