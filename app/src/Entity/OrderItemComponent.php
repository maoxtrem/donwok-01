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

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(length: 30)]
    private string $unit = 'unidad';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $costPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $salePrice = '0.00';

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getOrderItem(): ?OrderItem { return $this->orderItem; }
    public function setOrderItem(?OrderItem $orderItem): self { $this->orderItem = $orderItem; return $this; }
    public function getSourceProductId(): ?int { return $this->sourceProductId; }
    public function setSourceProductId(?int $sourceProductId): self { $this->sourceProductId = $sourceProductId; return $this; }
    public function getProductName(): string { return $this->productName; }
    public function setProductName(string $productName): self { $this->productName = trim($productName); return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }
    public function getUnit(): string { return $this->unit; }
    public function setUnit(string $unit): self { $this->unit = trim($unit); return $this; }
    public function getCostPrice(): string { return $this->costPrice; }
    public function setCostPrice(string $costPrice): self { $this->costPrice = $costPrice; return $this; }
    public function getSalePrice(): string { return $this->salePrice; }
    public function setSalePrice(string $salePrice): self { $this->salePrice = $salePrice; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
