<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'composite_components')]
#[ORM\HasLifecycleCallbacks]
class CompositeComponent
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Composite::class, inversedBy: 'components')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Composite $composite = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'compositeComponents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Product $product = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $quantity = '1.000';

    #[ORM\Column(length: 30)]
    private string $unit = 'unidad';

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getComposite(): ?Composite { return $this->composite; }
    public function setComposite(?Composite $composite): self { $this->composite = $composite; return $this; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): self { $this->product = $product; return $this; }
    public function getQuantity(): string { return $this->quantity; }
    public function setQuantity(string $quantity): self { $this->quantity = $quantity; return $this; }
    public function getUnit(): string { return $this->unit; }
    public function setUnit(string $unit): self { $this->unit = trim($unit); return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
