<?php

namespace App\Controller;

use App\Entity\PermissionGroup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'app_admin_users', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/users.html.twig', [
            'user' => $this->getUser(),
            'users' => $this->entityManager->getRepository(User::class)->findBy([], ['username' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        return $this->save($request, $csrf, new User(), true);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException('El usuario no existe.');
        }

        return $this->save($request, $csrf, $user, false);
    }

    #[Route('/{id}/deactivate', name: 'app_admin_user_deactivate', methods: ['POST'])]
    public function deactivate(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException('El usuario no existe.');
        }

        if ($user === $this->getUser()) {
            throw $this->createAccessDeniedException('El usuario administrador actual no puede desactivarse a sí mismo.');
        }

        if (!$csrf->isTokenValid(new CsrfToken('admin_user_deactivate_'.$id, (string) $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $user->setActive(false);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }

    private function save(Request $request, CsrfTokenManagerInterface $csrf, User $user, bool $new): Response
    {
        $groups = $this->entityManager->getRepository(PermissionGroup::class)->findBy([], ['name' => 'ASC']);
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$csrf->isTokenValid(new CsrfToken('admin_user', (string) $request->request->get('_token')))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $username = mb_strtolower(trim((string) $request->request->get('username')));
            $password = (string) $request->request->get('password');
            $role = (string) $request->request->get('role', 'ROLE_USER');
            $existing = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            $isSelfAdmin = $user === $this->getUser() && in_array('ROLE_ADMIN', $user->getRoles(), true);

            if ($username === '') {
                $error = 'El nombre de usuario es obligatorio.';
            } elseif ($existing instanceof User && $existing !== $user) {
                $error = 'Ese nombre de usuario ya existe.';
            } elseif ($new && $password === '') {
                $error = 'La contraseña es obligatoria para crear el usuario.';
            } elseif (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'], true)) {
                $error = 'El rol seleccionado no es válido.';
            } elseif ($isSelfAdmin && $role !== 'ROLE_ADMIN') {
                $error = 'El administrador actual no puede cambiarse su propio rol.';
            } elseif ($isSelfAdmin && !$request->request->has('active')) {
                $error = 'El administrador actual no puede desactivarse a sí mismo.';
            } else {
                $user->setUsername($username)->setRoles([$role])->setActive($request->request->has('active'));

                if ($password !== '') {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                }

                foreach ($user->getPermissionGroups()->toArray() as $group) {
                    $user->removePermissionGroup($group);
                }

                if ($role === 'ROLE_USER') {
                    $selectedGroups = $request->request->all('groups');
                    foreach ($groups as $group) {
                        if (in_array((string) $group->getId(), $selectedGroups, true)) {
                            $user->addPermissionGroup($group);
                        }
                    }
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => $this->getUser(),
            'account' => $user,
            'groups' => $groups,
            'new' => $new,
            'error' => $error,
            'is_self_admin' => $user === $this->getUser() && in_array('ROLE_ADMIN', $user->getRoles(), true),
        ]);
    }
}
