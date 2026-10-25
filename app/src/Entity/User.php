<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $username = '';

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\ManyToMany(targetEntity: PermissionGroup::class, mappedBy: 'users')]
    private Collection $permissionGroups;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Cashier::class)]
    private ?Cashier $cashier = null;

    public function __construct()
    {
        $this->permissionGroups = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }

    public function setUsername(string $username): self
    {
        $this->username = mb_strtolower(trim($username));
        return $this;
    }

    public function getUserIdentifier(): string { return $this->username; }

    public function getRoles(): array
    {
        return array_values(array_unique(array_merge(['ROLE_USER'], $this->roles)));
    }

    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }

    /** @return Collection<int, PermissionGroup> */
    public function getPermissionGroups(): Collection { return $this->permissionGroups; }

    public function addPermissionGroup(PermissionGroup $group): self
    {
        if (!$this->permissionGroups->contains($group)) {
            $this->permissionGroups->add($group);
            $group->addUser($this);
        }
        return $this;
    }

    public function removePermissionGroup(PermissionGroup $group): self
    {
        if ($this->permissionGroups->removeElement($group)) {
            $group->removeUser($this);
        }
        return $this;
    }

    public function getCashier(): ?Cashier { return $this->cashier; }

    public function eraseCredentials(): void {}
}
