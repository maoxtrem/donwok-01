<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cash_closure_lines')]
class CashClosureLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CashClosure::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CashClosure $closure = null;

    #[ORM\ManyToOne(targetEntity: CashBox::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CashBox $cashBox = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $expected = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $counted = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $difference = '0.00';

    public function getId(): ?int { return $this->id; }
    public function getClosure(): ?CashClosure { return $this->closure; }
    public function setClosure(CashClosure $closure): self { $this->closure = $closure; return $this; }
    public function getCashBox(): ?CashBox { return $this->cashBox; }
    public function setCashBox(CashBox $cashBox): self { $this->cashBox = $cashBox; return $this; }
    public function getExpected(): string { return $this->expected; }
    public function setExpected(string $value): self { $this->expected = $value; return $this; }
    public function getCounted(): string { return $this->counted; }
    public function setCounted(string $value): self { $this->counted = $value; return $this; }
    public function getDifference(): string { return $this->difference; }
    public function setDifference(string $value): self { $this->difference = $value; return $this; }
}
