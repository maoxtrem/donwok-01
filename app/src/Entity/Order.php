<?php

namespace App\Entity;

use App\Entity\Enum\OrderBillingStatus;
use App\Entity\Enum\OrderOperationalStatus;
use App\Entity\Enum\OrderType;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uniq_orders_date_number', columns: ['orderDate', 'orderNumber'])]
#[ORM\HasLifecycleCallbacks]
class Order
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $orderDate;

    #[ORM\Column]
    private int $orderNumber = 0;

    #[ORM\Column(type: 'string', enumType: OrderType::class, length: 20)]
    private OrderType $orderType = OrderType::TABLE;

    #[ORM\Column(type: 'string', enumType: OrderOperationalStatus::class, length: 30)]
    private OrderOperationalStatus $operationalStatus = OrderOperationalStatus::PENDING;

    #[ORM\Column(type: 'string', enumType: OrderBillingStatus::class, length: 20)]
    private OrderBillingStatus $billingStatus = OrderBillingStatus::PENDING;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $tableNumber = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(type: 'string', enumType: PaymentMethod::class, length: 20, nullable: true)]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $deliveryFee = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $cashAmount = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $digitalAmount = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $cashReceived = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $changeDue = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedCashier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentReceiptKey = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $paymentReceiptRequested = false;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $paymentReceiptOriginalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $paymentReceiptMimeType = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paymentReceiptUploadedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $paymentReceiptUploadedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $lockedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lockedAt = null;

    /** @var Collection<int, OrderPaymentAllocation> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderPaymentAllocation::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $paymentAllocations;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    /** @var Collection<int, OrderHistory> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderHistory::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $history;

    #[ORM\OneToOne(mappedBy: 'order', targetEntity: Invoice::class)]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->orderDate = new DateTimeImmutable('today');
        $this->items = new ArrayCollection();
        $this->history = new ArrayCollection();
        $this->paymentAllocations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getOrderDate(): DateTimeImmutable { return $this->orderDate; }
    public function setOrderDate(DateTimeImmutable $orderDate): self { $this->orderDate = $orderDate; return $this; }
    public function getOrderNumber(): int { return $this->orderNumber; }
    public function setOrderNumber(int $orderNumber): self { $this->orderNumber = $orderNumber; return $this; }
    public function getOrderType(): OrderType { return $this->orderType; }
    public function setOrderType(OrderType $orderType): self { $this->orderType = $orderType; return $this; }
    public function getOperationalStatus(): OrderOperationalStatus { return $this->operationalStatus; }
    public function setOperationalStatus(OrderOperationalStatus $status): self { $this->operationalStatus = $status; return $this; }
    public function getBillingStatus(): OrderBillingStatus { return $this->billingStatus; }
    public function setBillingStatus(OrderBillingStatus $status): self { $this->billingStatus = $status; return $this; }
    public function getTableNumber(): ?string { return $this->tableNumber; }
    public function setTableNumber(?string $tableNumber): self { $this->tableNumber = $tableNumber !== null ? trim($tableNumber) : null; return $this; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(?string $customerName): self { $this->customerName = $customerName !== null ? trim($customerName) : null; return $this; }
    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function setCustomerPhone(?string $customerPhone): self { $this->customerPhone = $customerPhone !== null ? trim($customerPhone) : null; return $this; }
    public function getDeliveryAddress(): ?string { return $this->deliveryAddress; }
    public function setDeliveryAddress(?string $deliveryAddress): self { $this->deliveryAddress = $deliveryAddress !== null ? trim($deliveryAddress) : null; return $this; }
    public function getPaymentMethod(): ?PaymentMethod { return $this->paymentMethod; }
    public function setPaymentMethod(?PaymentMethod $paymentMethod): self { $this->paymentMethod = $paymentMethod; return $this; }
    public function getDeliveryFee(): string { return $this->deliveryFee; }
    public function setDeliveryFee(string $deliveryFee): self { $this->deliveryFee = $deliveryFee; return $this; }
    public function getCashAmount(): ?string { return $this->cashAmount; }
    public function setCashAmount(?string $cashAmount): self { $this->cashAmount = $cashAmount; return $this; }
    public function getDigitalAmount(): ?string { return $this->digitalAmount; }
    public function setDigitalAmount(?string $digitalAmount): self { $this->digitalAmount = $digitalAmount; return $this; }
    public function getCashReceived(): ?string { return $this->cashReceived; }
    public function setCashReceived(?string $cashReceived): self { $this->cashReceived = $cashReceived; return $this; }
    public function getChangeDue(): ?string { return $this->changeDue; }
    public function setChangeDue(?string $changeDue): self { $this->changeDue = $changeDue; return $this; }
    public function getAssignedCashier(): ?User { return $this->assignedCashier; }
    public function setAssignedCashier(?User $cashier): self { $this->assignedCashier = $cashier; return $this; }
    public function getPaymentReceiptKey(): ?string { return $this->paymentReceiptKey; }
    public function setPaymentReceiptKey(?string $key): self { $this->paymentReceiptKey = $key; return $this; }
    public function isPaymentReceiptRequested(): bool { return $this->paymentReceiptRequested; }
    public function setPaymentReceiptRequested(bool $requested): self { $this->paymentReceiptRequested = $requested; return $this; }
    public function getPaymentReceiptOriginalName(): ?string { return $this->paymentReceiptOriginalName; }
    public function setPaymentReceiptOriginalName(?string $name): self { $this->paymentReceiptOriginalName = $name; return $this; }
    public function getPaymentReceiptMimeType(): ?string { return $this->paymentReceiptMimeType; }
    public function setPaymentReceiptMimeType(?string $mimeType): self { $this->paymentReceiptMimeType = $mimeType; return $this; }
    public function getPaymentReceiptUploadedAt(): ?DateTimeImmutable { return $this->paymentReceiptUploadedAt; }
    public function setPaymentReceiptUploadedAt(?DateTimeImmutable $uploadedAt): self { $this->paymentReceiptUploadedAt = $uploadedAt; return $this; }
    public function getPaymentReceiptUploadedBy(): ?User { return $this->paymentReceiptUploadedBy; }
    public function setPaymentReceiptUploadedBy(?User $user): self { $this->paymentReceiptUploadedBy = $user; return $this; }
    public function getLockedBy(): ?User { return $this->lockedBy; }
    public function setLockedBy(?User $user): self { $this->lockedBy = $user; return $this; }
    public function getLockedAt(): ?DateTimeImmutable { return $this->lockedAt; }
    public function setLockedAt(?DateTimeImmutable $lockedAt): self { $this->lockedAt = $lockedAt; return $this; }
    /** @return Collection<int, OrderPaymentAllocation> */
    public function getPaymentAllocations(): Collection { return $this->paymentAllocations; }
    public function addPaymentAllocation(OrderPaymentAllocation $allocation): self
    {
        if (!$this->paymentAllocations->contains($allocation)) {
            $this->paymentAllocations->add($allocation);
            $allocation->setOrder($this);
        }
        return $this;
    }
    public function removePaymentAllocation(OrderPaymentAllocation $allocation): self
    {
        $this->paymentAllocations->removeElement($allocation);
        return $this;
    }

    public function isInvoiced(): bool { return $this->billingStatus === OrderBillingStatus::INVOICED; }

    public function getTotal(): string
    {
        $totalInCents = 0;

        foreach ($this->items as $item) {
            $value = $item->getTotal();
            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
            $totalInCents += ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        }

        return sprintf('%d.%02d', intdiv($totalInCents, 100), $totalInCents % 100);
    }

    public function getPayableTotal(): string
    {
        $totalInCents = $this->moneyToCents($this->getTotal()) + $this->moneyToCents($this->getDeliveryFee());

        return sprintf('%d.%02d', intdiv($totalInCents, 100), $totalInCents % 100);
    }

    private function moneyToCents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item) && $item->getOrder() === $this) {
            $item->setOrder(null);
        }

        return $this;
    }

    /** @return Collection<int, OrderHistory> */
    public function getHistory(): Collection { return $this->history; }

    public function addHistory(OrderHistory $entry): self
    {
        if (!$this->history->contains($entry)) {
            $this->history->add($entry);
            $entry->setOrder($this);
        }

        return $this;
    }

    public function getInvoice(): ?Invoice { return $this->invoice; }
}
