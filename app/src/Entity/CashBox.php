<?php

namespace App\Entity;

use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\CashBoxMoneyType;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cash_boxes')]
#[ORM\HasLifecycleCallbacks]
class CashBox
{
    use TimestampableTrait;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cashier::class, inversedBy: 'boxes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Cashier $cashier = null;

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(type: 'string', enumType: CashBoxMoneyType::class, length: 20)]
    private CashBoxMoneyType $moneyType = CashBoxMoneyType::CASH;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $balance = '0.00';

    #[ORM\Column(type: 'string', enumType: PaymentMethod::class, length: 20, nullable: true)]
    private ?PaymentMethod $defaultPaymentMethod = null;

    public function __construct()
    {
        $hex = bin2hex(random_bytes(16));
        $this->uuid = sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getCashier(): ?Cashier { return $this->cashier; }
    public function setCashier(?Cashier $cashier): self { $this->cashier = $cashier; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }
    public function getMoneyType(): CashBoxMoneyType { return $this->moneyType; }
    public function setMoneyType(CashBoxMoneyType $moneyType): self { $this->moneyType = $moneyType; return $this; }
    public function getBalance(): string { return $this->balance; }
    public function setBalance(string $balance): self { $this->balance = $balance; return $this; }
    public function getDefaultPaymentMethod(): ?PaymentMethod { return $this->defaultPaymentMethod; }
    public function setDefaultPaymentMethod(?PaymentMethod $method): self { $this->defaultPaymentMethod = $method; return $this; }
}
