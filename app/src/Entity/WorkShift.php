<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'work_shifts')]
#[ORM\HasLifecycleCallbacks]
class WorkShift
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkPosition::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?WorkPosition $position = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $hourlyRate = '0.00';

    #[ORM\Column(type: 'integer')]
    private int $minimumDenomination = 50;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $amountToPay = null;

    #[ORM\Column(length: 64)]
    private string $closeCodeHash = '';

    public function __construct(WorkPosition $position, ?User $openedBy = null)
    {
        $this->position = $position;
        $this->startedAt = new \DateTimeImmutable();
        $this->hourlyRate = $position->getHourlyRate() ?? '0.00';
        $this->minimumDenomination = $position->getMinimumDenomination();
        $this->closeCodeHash = hash('sha256', $this->generateCloseCode());
        $this->setCreatedBy($openedBy)->setUpdatedBy($openedBy);
    }

    public function close(string $code, ?User $closedBy = null): void
    {
        if (!hash_equals($this->closeCodeHash, hash('sha256', strtoupper(trim($code)))) ) {
            throw new \InvalidArgumentException('El código de cierre no es válido.');
        }
        $this->closedAt = new \DateTimeImmutable();
        $minutes = $this->getWorkedMinutes();
        $exact = $minutes * ((float) $this->hourlyRate / 60);
        $this->amountToPay = number_format(ceil($exact / $this->minimumDenomination) * $this->minimumDenomination, 2, '.', '');
        $this->setUpdatedBy($closedBy);
    }

    public function closeByAdmin(?User $closedBy = null): void
    {
        if (!$this->isActive()) {
            throw new \InvalidArgumentException('El turno ya está cerrado.');
        }

        $this->closedAt = new \DateTimeImmutable();
        $minutes = $this->getWorkedMinutes();
        $exact = $minutes * ((float) $this->hourlyRate / 60);
        $this->amountToPay = number_format(ceil($exact / $this->minimumDenomination) * $this->minimumDenomination, 2, '.', '');
        $this->setUpdatedBy($closedBy);
    }

    public function getId(): ?int { return $this->id; }
    public function getPosition(): ?WorkPosition { return $this->position; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
    public function getHourlyRate(): string { return $this->hourlyRate; }
    public function getMinimumDenomination(): int { return $this->minimumDenomination; }
    public function getAmountToPay(): ?string { return $this->amountToPay; }
    public function isActive(): bool { return $this->closedAt === null; }
    public function getWorkedMinutes(): int
    {
        $end = $this->closedAt ?? new \DateTimeImmutable();
        return intdiv(max(0, $end->getTimestamp() - $this->startedAt->getTimestamp()), 60);
    }
    private function generateCloseCode(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $letters[random_int(0, strlen($letters) - 1)];
        }
        $this->plainCloseCode = substr($code, 0, 2) . '-' . substr($code, 2, 2);
        return $this->plainCloseCode;
    }

    private string $plainCloseCode = '';

    public function getPlainCloseCode(): string { return $this->plainCloseCode; }
}
