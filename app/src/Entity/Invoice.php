<?php

namespace App\Entity;

use App\Entity\Enum\PaymentMethod;
use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoices')]
#[ORM\UniqueConstraint(name: 'uniq_invoices_number', columns: ['invoiceNumber'])]
#[ORM\HasLifecycleCallbacks]
class Invoice
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $invoiceNumber = 0;

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: 'invoice')]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'RESTRICT')]
    private ?Order $order = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $issuedAt;

    #[ORM\Column(type: 'string', enumType: PaymentMethod::class, length: 20)]
    private PaymentMethod $paymentMethod;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $subtotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $deliveryFee = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $cashAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $digitalAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $cashReceived = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $changeDue = null;

    #[ORM\Column(type: 'json')]
    private array $itemsSnapshot = [];

    public function __construct()
    {
        $this->issuedAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getInvoiceNumber(): int { return $this->invoiceNumber; }
    public function setInvoiceNumber(int $number): self { $this->invoiceNumber = $number; return $this; }
    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(Order $order): self { $this->order = $order; return $this; }
    public function getIssuedAt(): DateTimeImmutable { return $this->issuedAt; }
    public function setIssuedAt(DateTimeImmutable $issuedAt): self { $this->issuedAt = $issuedAt; return $this; }
    public function getPaymentMethod(): PaymentMethod { return $this->paymentMethod; }
    public function setPaymentMethod(PaymentMethod $method): self { $this->paymentMethod = $method; return $this; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function setSubtotal(string $value): self { $this->subtotal = $value; return $this; }
    public function getDeliveryFee(): string { return $this->deliveryFee; }
    public function setDeliveryFee(string $value): self { $this->deliveryFee = $value; return $this; }
    public function getTotal(): string { return $this->total; }
    public function setTotal(string $value): self { $this->total = $value; return $this; }
    public function getCashAmount(): string { return $this->cashAmount; }
    public function setCashAmount(string $value): self { $this->cashAmount = $value; return $this; }
    public function getDigitalAmount(): string { return $this->digitalAmount; }
    public function setDigitalAmount(string $value): self { $this->digitalAmount = $value; return $this; }
    public function getCashReceived(): ?string { return $this->cashReceived; }
    public function setCashReceived(?string $value): self { $this->cashReceived = $value; return $this; }
    public function getChangeDue(): ?string { return $this->changeDue; }
    public function setChangeDue(?string $value): self { $this->changeDue = $value; return $this; }
    public function getItemsSnapshot(): array { return $this->itemsSnapshot; }
    public function setItemsSnapshot(array $items): self { $this->itemsSnapshot = $items; return $this; }
}
