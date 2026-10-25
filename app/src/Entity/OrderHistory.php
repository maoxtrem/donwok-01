<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_history')]
#[ORM\HasLifecycleCallbacks]
class OrderHistory
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'history')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $performedBy = null;

    #[ORM\Column(length: 60)]
    private string $action = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $previousStatus = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $newStatus = null;

    #[ORM\Column(type: 'json')]
    private array $details = [];

    public function getId(): ?int { return $this->id; }
    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): self { $this->order = $order; return $this; }
    public function getPerformedBy(): ?User { return $this->performedBy; }
    public function setPerformedBy(?User $user): self { $this->performedBy = $user; return $this; }
    public function getAction(): string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }
    public function getPreviousStatus(): ?string { return $this->previousStatus; }
    public function setPreviousStatus(?string $status): self { $this->previousStatus = $status; return $this; }
    public function getNewStatus(): ?string { return $this->newStatus; }
    public function setNewStatus(?string $status): self { $this->newStatus = $status; return $this; }
    public function getDetails(): array { return $this->details; }
    public function setDetails(array $details): self { $this->details = $details; return $this; }
}
