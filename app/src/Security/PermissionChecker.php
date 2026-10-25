<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

final class PermissionChecker
{
    public function __construct(private readonly Security $security)
    {
    }

    public function isGranted(string $permissionCode): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        foreach ($user->getPermissionGroups() as $group) {
            if (!$group->isActive()) {
                continue;
            }

            foreach ($group->getPermissions() as $permission) {
                if ($permission->getCode() === $permissionCode) {
                    return true;
                }
            }
        }

        return false;
    }
}
