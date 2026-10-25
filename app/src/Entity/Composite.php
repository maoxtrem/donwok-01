<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'composites')]
#[ORM\HasLifecycleCallbacks]
class Composite
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column(length: 120, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $costPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $salePrice = '0.00';

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /** @var Collection<int, CompositeComponent> */
    #[ORM\OneToMany(mappedBy: 'composite', targetEntity: CompositeComponent::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $components;

    public function __construct()
    {
        $this->components = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code !== null ? trim($code) : null; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getCostPrice(): string { return $this->costPrice; }
    public function setCostPrice(string $costPrice): self { $this->costPrice = $costPrice; return $this; }
    public function getSalePrice(): string { return $this->salePrice; }
    public function setSalePrice(string $salePrice): self { $this->salePrice = $salePrice; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }

    /** @return Collection<int, CompositeComponent> */
    public function getComponents(): Collection { return $this->components; }

    public function addComponent(CompositeComponent $component): self
    {
        if (!$this->components->contains($component)) {
            $this->components->add($component);
            $component->setComposite($this);
        }

        $this->refreshPricesFromComponents();

        return $this;
    }

    public function removeComponent(CompositeComponent $component): self
    {
        if ($this->components->removeElement($component) && $component->getComposite() === $this) {
            $component->setComposite(null);
        }

        $this->refreshPricesFromComponents();

        return $this;
    }

    public function refreshPricesFromComponents(): self
    {
        $costCents = 0;
        $saleCents = 0;

        foreach ($this->components as $component) {
            $costCents += self::toCents($component->getCostPrice());
            $saleCents += self::toCents($component->getSalePrice());
        }

        $this->costPrice = self::fromCents($costCents);
        $this->salePrice = self::fromCents($saleCents);

        return $this;
    }

    private static function toCents(string $amount): int
    {
        [$whole, $decimal] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private static function fromCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
