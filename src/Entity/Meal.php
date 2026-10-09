<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A meal of the household library: a name and a list of products (no quantities).
 * Seasons restrict automatic planning: an out-of-season meal is never generated, only placed manually.
 */
#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
{
    public const SEASONS = ['spring', 'summer', 'autumn', 'winter'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Household $household = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var list<string>|null Null (or empty) means all year round */
    #[ORM\Column(nullable: true)]
    private ?array $seasons = null;

    #[ORM\Column]
    private bool $isFavorite = false;

    /** @var Collection<int, Product> */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'meal_product')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    private Collection $products;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Season of a given date (meteorological seasons, northern hemisphere).
     */
    public static function seasonOf(\DateTimeInterface $date): string
    {
        $month = (int) $date->format('n');

        return match (true) {
            $month >= 3 && $month <= 5 => 'spring',
            $month >= 6 && $month <= 8 => 'summer',
            $month >= 9 && $month <= 11 => 'autumn',
            default => 'winter',
        };
    }

    public function isInSeason(\DateTimeInterface $date): bool
    {
        return empty($this->seasons) || in_array(self::seasonOf($date), $this->seasons, true);
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
        $this->name = trim($name);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $description = $description !== null ? trim($description) : null;
        $this->description = $description === '' ? null : $description;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getSeasons(): array
    {
        return $this->seasons ?? [];
    }

    /**
     * @param list<string> $seasons
     */
    public function setSeasons(array $seasons): static
    {
        // Keep the canonical order and drop unknown values
        $seasons = array_values(array_intersect(self::SEASONS, $seasons));
        $this->seasons = $seasons ?: null;

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @param iterable<Product> $products
     */
    public function setProducts(iterable $products): static
    {
        $this->products->clear();
        foreach ($products as $product) {
            if (!$this->products->contains($product)) {
                $this->products->add($product);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
