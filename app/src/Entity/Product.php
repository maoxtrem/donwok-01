<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column(length: 120, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $costPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $salePrice = '0.00';

    #[ORM\Column(length: 30)]
    private string $unit = 'unidad';

    #[ORM\Column(options: ['default' => 1])]
    private int $measure = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    /** @var Collection<int, CompositeComponent> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: CompositeComponent::class)]
    private Collection $compositeComponents;

    public function __construct()
    {
        $this->compositeComponents = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code !== null ? trim($code) : null; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getCostPrice(): string { return $this->costPrice; }
    public function setCostPrice(string $costPrice): self { $this->costPrice = $costPrice; return $this; }
    public function getSalePrice(): string { return $this->salePrice; }
    public function setSalePrice(string $salePrice): self { $this->salePrice = $salePrice; return $this; }
    public function getUnit(): string { return $this->unit; }
    public function setUnit(string $unit): self { $this->unit = trim($unit); return $this; }
    public function getMeasure(): int { return $this->measure; }
    public function setMeasure(int $measure): self { $this->measure = $measure; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): self { $this->category = $category; return $this; }

    /** @return Collection<int, CompositeComponent> */
    public function getCompositeComponents(): Collection { return $this->compositeComponents; }
}
