<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_item_components')]
#[ORM\HasLifecycleCallbacks]
class OrderItemComponent
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderItem::class, inversedBy: 'components')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OrderItem $orderItem = null;

    /** This is a catalog reference only; it is not a foreign key. */
    #[ORM\Column(nullable: true)]
    private ?int $sourceProductId = null;

    #[ORM\Column(length: 180)]
    private string $productName = '';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $quantity = '1.000';

    #[ORM\Column(length: 30)]
    private string $unit = 'unidad';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $unitPrice = '0.00';

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getOrderItem(): ?OrderItem { return $this->orderItem; }
    public function setOrderItem(?OrderItem $orderItem): self { $this->orderItem = $orderItem; return $this; }
    public function getSourceProductId(): ?int { return $this->sourceProductId; }
    public function setSourceProductId(?int $sourceProductId): self { $this->sourceProductId = $sourceProductId; return $this; }
    public function getProductName(): string { return $this->productName; }
    public function setProductName(string $productName): self { $this->productName = trim($productName); return $this; }
    public function getQuantity(): string { return $this->quantity; }
    public function setQuantity(string $quantity): self { $this->quantity = $quantity; return $this; }
    public function getUnit(): string { return $this->unit; }
    public function setUnit(string $unit): self { $this->unit = trim($unit); return $this; }
    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
