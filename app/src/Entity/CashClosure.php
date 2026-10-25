<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cash_closures')]
#[ORM\HasLifecycleCallbacks]
class CashClosure
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cashier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Cashier $cashier = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $closedBy = null;

    #[ORM\Column(length: 20)]
    private string $status = 'cerrado';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $expectedTotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $countedTotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $differenceTotal = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $observations = null;

    /** @var Collection<int, CashClosureLine> */
    #[ORM\OneToMany(mappedBy: 'closure', targetEntity: CashClosureLine::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCashier(): ?Cashier { return $this->cashier; }
    public function setCashier(Cashier $cashier): self { $this->cashier = $cashier; return $this; }
    public function getClosedBy(): ?User { return $this->closedBy; }
    public function setClosedBy(User $user): self { $this->closedBy = $user; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getExpectedTotal(): string { return $this->expectedTotal; }
    public function setExpectedTotal(string $value): self { $this->expectedTotal = $value; return $this; }
    public function getCountedTotal(): string { return $this->countedTotal; }
    public function setCountedTotal(string $value): self { $this->countedTotal = $value; return $this; }
    public function getDifferenceTotal(): string { return $this->differenceTotal; }
    public function setDifferenceTotal(string $value): self { $this->differenceTotal = $value; return $this; }
    public function getObservations(): ?string { return $this->observations; }
    public function setObservations(?string $value): self { $this->observations = $value !== null ? trim($value) : null; return $this; }
    /** @return Collection<int, CashClosureLine> */
    public function getLines(): Collection { return $this->lines; }
    public function addLine(CashClosureLine $line): self { if (!$this->lines->contains($line)) { $this->lines->add($line); $line->setClosure($this); } return $this; }
}
