<?php

namespace App\Controller;

use App\Entity\Permission;
use App\Entity\PermissionGroup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminSecurityController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/groups/new', name: 'app_admin_group_new', methods: ['GET', 'POST'])]
    public function newGroup(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $group = new PermissionGroup();

        return $this->saveGroup($request, $csrf, $group);
    }

    #[Route('/groups/{id}/edit', name: 'app_admin_group_edit', methods: ['GET', 'POST'])]
    public function editGroup(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $group = $this->entityManager->getRepository(PermissionGroup::class)->find($id);

        if (!$group instanceof PermissionGroup) {
            throw $this->createNotFoundException('El grupo no existe.');
        }

        return $this->saveGroup($request, $csrf, $group);
    }

    #[Route('/groups', name: 'app_admin_groups', methods: ['GET'])]
    public function groups(): Response
    {
        return $this->render('admin/groups.html.twig', [
            'user' => $this->getUser(),
            'groups' => $this->entityManager->getRepository(PermissionGroup::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    private function saveGroup(Request $request, CsrfTokenManagerInterface $csrf, PermissionGroup $group): Response
    {
        $permissions = $this->entityManager->getRepository(Permission::class)->findBy([], ['resource' => 'ASC', 'action' => 'ASC']);
        $users = $this->entityManager->getRepository(User::class)->findBy(['active' => true], ['username' => 'ASC']);

        if ($request->isMethod('POST')) {
            if (!$csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('admin_group', (string) $request->request->get('_token')))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $name = trim((string) $request->request->get('name'));
            if ($name === '') {
                return $this->renderGroupForm($group, $permissions, $users, 'El nombre del grupo es obligatorio.');
            }

            $group->setName($name)
                ->setDescription(trim((string) $request->request->get('description')) ?: null)
                ->setActive($request->request->has('active'));

            foreach ($group->getPermissions()->toArray() as $permission) {
                $group->removePermission($permission);
            }
            foreach ($permissions as $permission) {
                if ($request->request->all('permissions') && in_array((string) $permission->getId(), $request->request->all('permissions'), true)) {
                    $group->addPermission($permission);
                }
            }

            foreach ($group->getUsers()->toArray() as $user) {
                $group->removeUser($user);
            }
            foreach ($users as $user) {
                if ($request->request->all('users') && in_array((string) $user->getId(), $request->request->all('users'), true)) {
                    $group->addUser($user);
                }
            }

            $this->entityManager->persist($group);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_groups');
        }

        return $this->renderGroupForm($group, $permissions, $users);
    }

    private function renderGroupForm(PermissionGroup $group, array $permissions, array $users, ?string $error = null): Response
    {
        $permissionMatrix = [];
        foreach ($permissions as $permission) {
            $permissionMatrix[$permission->getResource()][] = $permission;
        }

        return $this->render('admin/group_form.html.twig', [
            'user' => $this->getUser(),
            'group' => $group,
            'permissions' => $permissions,
            'permission_matrix' => $permissionMatrix,
            'users' => $users,
            'error' => $error,
        ]);
    }
}
