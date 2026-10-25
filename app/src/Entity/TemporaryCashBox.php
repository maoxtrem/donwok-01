<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'temporary_cash_boxes')]
#[ORM\HasLifecycleCallbacks]
class TemporaryCashBox
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: CashClosure::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CashClosure $closure = null;

    #[ORM\ManyToOne(targetEntity: CashBox::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CashBox $sourceBox = null;

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $balance = '0.00';

    public function __construct()
    {
        $this->uuid = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getClosure(): ?CashClosure { return $this->closure; }
    public function setClosure(CashClosure $closure): self { $this->closure = $closure; return $this; }
    public function getSourceBox(): ?CashBox { return $this->sourceBox; }
    public function setSourceBox(CashBox $box): self { $this->sourceBox = $box; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $value): self { $this->name = trim($value); return $this; }
    public function getBalance(): string { return $this->balance; }
    public function setBalance(string $value): self { $this->balance = $value; return $this; }
}
