<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cash_ledger_entries')]
#[ORM\HasLifecycleCallbacks]
class CashLedgerEntry
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CashBox::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CashBox $cashBox = null;

    #[ORM\Column(length: 30)]
    private string $type = '';

    #[ORM\Column(length: 20)]
    private string $status = 'validado';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 255)]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptOriginalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $receiptMimeType = null;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: CashMovement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CashMovement $movement = null;

    public function getId(): ?int { return $this->id; }
    public function getCashBox(): ?CashBox { return $this->cashBox; }
    public function setCashBox(CashBox $cashBox): self { $this->cashBox = $cashBox; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self { $this->amount = $amount; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = trim($description); return $this; }
    public function getReceiptKey(): ?string { return $this->receiptKey; }
    public function setReceiptKey(?string $receiptKey): self { $this->receiptKey = $receiptKey; return $this; }
    public function getReceiptOriginalName(): ?string { return $this->receiptOriginalName; }
    public function setReceiptOriginalName(?string $name): self { $this->receiptOriginalName = $name; return $this; }
    public function getReceiptMimeType(): ?string { return $this->receiptMimeType; }
    public function setReceiptMimeType(?string $mimeType): self { $this->receiptMimeType = $mimeType; return $this; }
    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): self { $this->order = $order; return $this; }
    public function getMovement(): ?CashMovement { return $this->movement; }
    public function setMovement(?CashMovement $movement): self { $this->movement = $movement; return $this; }
}
