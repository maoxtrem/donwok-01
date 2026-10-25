<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cashiers')]
#[ORM\HasLifecycleCallbacks]
class Cashier
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, options: ['default' => 'Cajero'])]
    private string $name = 'Cajero';

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'cashier')]
    #[ORM\JoinColumn(nullable: true, unique: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /** @var Collection<int, CashBox> */
    #[ORM\OneToMany(mappedBy: 'cashier', targetEntity: CashBox::class, cascade: ['persist'])]
    private Collection $boxes;

    public function __construct()
    {
        $hex = bin2hex(random_bytes(16));
        $this->uuid = sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
        $this->boxes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name) !== '' ? trim($name) : 'Cajero'; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }

    /** @return Collection<int, CashBox> */
    public function getBoxes(): Collection { return $this->boxes; }

    public function addBox(CashBox $box): self
    {
        if (!$this->boxes->contains($box)) {
            $this->boxes->add($box);
            $box->setCashier($this);
        }

        return $this;
    }

    public function getActiveBox(): ?CashBox
    {
        if (!$this->active) {
            return null;
        }

        foreach ($this->boxes as $box) {
            return $box;
        }

        return null;
    }

    /** @return list<CashBox> */
    public function getActiveBoxes(): array
    {
        return $this->active ? $this->boxes->toArray() : [];
    }
}
