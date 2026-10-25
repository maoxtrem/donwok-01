<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cash_movements')]
#[ORM\HasLifecycleCallbacks]
class CashMovement
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $type = 'traslado';

    #[ORM\ManyToOne(targetEntity: CashBox::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CashBox $sourceBox = null;

    #[ORM\ManyToOne(targetEntity: CashBox::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CashBox $destinationBox = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 255)]
    private string $reason = '';

    public function getId(): ?int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getSourceBox(): ?CashBox { return $this->sourceBox; }
    public function setSourceBox(CashBox $box): self { $this->sourceBox = $box; return $this; }
    public function getDestinationBox(): ?CashBox { return $this->destinationBox; }
    public function setDestinationBox(CashBox $box): self { $this->destinationBox = $box; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self { $this->amount = $amount; return $this; }
    public function getReason(): string { return $this->reason; }
    public function setReason(string $reason): self { $this->reason = trim($reason); return $this; }
}
