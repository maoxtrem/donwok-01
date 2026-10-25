<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
#[ORM\HasLifecycleCallbacks]
class OrderItem
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\Column(length: 20)]
    private string $sourceType = '';

    #[ORM\Column(nullable: true)]
    private ?int $sourceId = null;

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $costPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $salePrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column]
    private int $position = 0;

    /** @var Collection<int, OrderItemComponent> */
    #[ORM\OneToMany(mappedBy: 'orderItem', targetEntity: OrderItemComponent::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $components;

    public function __construct()
    {
        $this->components = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): self { $this->order = $order; return $this; }
    public function getSourceType(): string { return $this->sourceType; }
    public function setSourceType(string $sourceType): self { $this->sourceType = trim($sourceType); return $this; }
    public function getSourceId(): ?int { return $this->sourceId; }
    public function setSourceId(?int $sourceId): self { $this->sourceId = $sourceId; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }
    public function getCostPrice(): string { return $this->costPrice; }
    public function setCostPrice(string $costPrice): self { $this->costPrice = $costPrice; return $this; }
    public function getSalePrice(): string { return $this->salePrice; }
    public function setSalePrice(string $salePrice): self { $this->salePrice = $salePrice; return $this; }
    public function getTotal(): string { return $this->total; }
    public function setTotal(string $total): self { $this->total = $total; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }

    /** @return Collection<int, OrderItemComponent> */
    public function getComponents(): Collection { return $this->components; }

    public function addComponent(OrderItemComponent $component): self
    {
        if (!$this->components->contains($component)) {
            $this->components->add($component);
            $component->setOrderItem($this);
        }

        return $this;
    }

    public function removeComponent(OrderItemComponent $component): self
    {
        if ($this->components->removeElement($component) && $component->getOrderItem() === $this) {
            $component->setOrderItem(null);
        }

        return $this;
    }
}
