<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'permission_groups')]
class PermissionGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'permissionGroups')]
    #[ORM\JoinTable(name: 'user_permission_groups')]
    private Collection $users;

    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'groups')]
    #[ORM\JoinTable(name: 'permission_group_permissions')]
    private Collection $permissions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }

    /** @return Collection<int, User> */
    public function getUsers(): Collection { return $this->users; }
    public function addUser(User $user): self { if (!$this->users->contains($user)) { $this->users->add($user); } return $this; }
    public function removeUser(User $user): self { $this->users->removeElement($user); return $this; }

    /** @return Collection<int, Permission> */
    public function getPermissions(): Collection { return $this->permissions; }
    public function addPermission(Permission $permission): self { if (!$this->permissions->contains($permission)) { $this->permissions->add($permission); } return $this; }
    public function removePermission(Permission $permission): self { $this->permissions->removeElement($permission); return $this; }
}
