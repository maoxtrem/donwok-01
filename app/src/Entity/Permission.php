<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;

#[ORM\Entity]
#[ORM\Table(name: 'permissions')]
#[ORM\HasLifecycleCallbacks]
class Permission
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column(length: 180)]
    private string $resource = '';

    #[ORM\Column(length: 30)]
    private string $action = '';

    #[ORM\ManyToMany(targetEntity: PermissionGroup::class, mappedBy: 'permissions')]
    private Collection $groups;

    public function __construct() { $this->groups = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getResource(): string { return $this->resource; }
    public function setResource(string $resource): self { $this->resource = $resource; return $this; }
    public function getAction(): string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }

    /** @return Collection<int, PermissionGroup> */
    public function getGroups(): Collection { return $this->groups; }

    public function addGroup(PermissionGroup $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->addPermission($this);
        }
        return $this;
    }
}
