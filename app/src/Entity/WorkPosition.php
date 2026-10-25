<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'work_positions')]
#[ORM\HasLifecycleCallbacks]
class WorkPosition
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $title = '';

    #[ORM\Column(length: 30, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 64)]
    private string $accessKeyHash = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $contractType = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $hourlyRate = null;

    #[ORM\Column(type: 'integer', options: ['default' => 50])]
    private int $minimumDenomination = 50;

    #[ORM\Column(type: 'json')]
    private array $activeDays = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function __construct(string $title, string $code, string $accessKey, ?string $description, ?string $hourlyRate, int $minimumDenomination, array $activeDays)
    {
        $this->update($title, $code, $accessKey, $description, $hourlyRate, $minimumDenomination, $activeDays, true);
    }

    public function update(string $title, string $code, ?string $accessKey, ?string $description, ?string $hourlyRate, int $minimumDenomination, array $activeDays, bool $creating = false): void
    {
        $title = trim($title);
        $code = strtoupper(trim($code));
        if ($title === '' || mb_strlen($title) > 150) {
            throw new \InvalidArgumentException('El nombre del puesto es obligatorio.');
        }
        if ($code === '' || mb_strlen($code) > 30) {
            throw new \InvalidArgumentException('El código del puesto es obligatorio.');
        }
        if ($hourlyRate !== null && ((float) $hourlyRate) <= 0) {
            throw new \InvalidArgumentException('El valor por hora debe ser mayor que cero.');
        }
        if ($minimumDenomination <= 0) {
            throw new \InvalidArgumentException('La denominación mínima debe ser mayor que cero.');
        }

        $this->title = $title;
        $this->code = $code;
        if ($creating || ($accessKey !== null && trim($accessKey) !== '')) {
            if (trim((string) $accessKey) === '') {
                throw new \InvalidArgumentException('La clave del puesto es obligatoria.');
            }
            $this->accessKeyHash = hash('sha256', strtoupper(trim((string) $accessKey)));
        }
        $this->description = $description !== null && trim($description) !== '' ? trim($description) : null;
        $this->hourlyRate = $hourlyRate !== null ? number_format((float) $hourlyRate, 2, '.', '') : null;
        $this->minimumDenomination = $minimumDenomination;
        $allowedDays = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $this->activeDays = array_values(array_intersect($allowedDays, array_map(static fn ($day) => mb_strtolower(trim((string) $day)), $activeDays)));
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getCode(): string { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function getHourlyRate(): ?string { return $this->hourlyRate; }
    public function getMinimumDenomination(): int { return $this->minimumDenomination; }
    public function getActiveDays(): array { return $this->activeDays; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    public function accessKeyIsValid(string $key): bool { return hash_equals($this->accessKeyHash, hash('sha256', strtoupper(trim($key)))); }
}
