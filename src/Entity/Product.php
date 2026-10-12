<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\Index(columns: ['name', 'description'], flags: ['fulltext'], name: 'idx_fulltext_search')]
#[ORM\Index(columns: ['price'], name: 'idx_price')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
#[ORM\Index(columns: ['rating'], name: 'idx_rating')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:list', 'product:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 200, unique: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $originalPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $rating = null;

    #[ORM\Column]
    #[Groups(['product:list', 'product:detail'])]
    private ?int $reviewCount = 0;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 20, options: ['default' => 'in_stock'])]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $stockStatus = 'in_stock';

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['product:detail'])]
    private ?string $sku = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['product:detail'])]
    private ?array $specifications = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?array $tags = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:list', 'product:detail'])]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Brand::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:list', 'product:detail'])]
    private ?Brand $brand = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['product:list', 'product:detail'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['product:detail'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // --- Getters & Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?string $originalPrice): static
    {
        $this->originalPrice = $originalPrice;
        return $this;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(string $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getReviewCount(): ?int
    {
        return $this->reviewCount;
    }

    public function setReviewCount(int $reviewCount): static
    {
        $this->reviewCount = $reviewCount;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getStockStatus(): ?string
    {
        return $this->stockStatus;
    }

    public function setStockStatus(string $stockStatus): static
    {
        $this->stockStatus = $stockStatus;
        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;
        return $this;
    }

    public function getSpecifications(): ?array
    {
        return $this->specifications;
    }

    public function setSpecifications(?array $specifications): static
    {
        $this->specifications = $specifications;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
        return $this;
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

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convert to array for Elasticsearch indexing.
     */
    public function toElasticDocument(): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => (float) $this->price,
            'original_price' => $this->originalPrice ? (float) $this->originalPrice : null,
            'rating'         => (float) $this->rating,
            'review_count'   => $this->reviewCount,
            'image_url'      => $this->imageUrl,
            'stock_status'   => $this->stockStatus,
            'sku'            => $this->sku,
            'specifications' => $this->specifications,
            'tags'           => $this->tags,
            'category_id'    => $this->category?->getId(),
            'category_name'  => $this->category?->getName(),
            'category_slug'  => $this->category?->getSlug(),
            'brand_id'       => $this->brand?->getId(),
            'brand_name'     => $this->brand?->getName(),
            'brand_slug'     => $this->brand?->getSlug(),
            'created_at'     => $this->createdAt?->format('Y-m-d\TH:i:s'),
            'updated_at'     => $this->updatedAt?->format('Y-m-d\TH:i:s'),
        ];
    }
}
