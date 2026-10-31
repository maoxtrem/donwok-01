<?php

namespace App\Controller;

use App\Entity\DiningTable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/tables')]
final class AdminDiningTableController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'app_admin_tables', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/tables.html.twig', [
            'user' => $this->getUser(),
            'tables' => $this->entityManager->getRepository(DiningTable::class)->findBy([], ['position' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_table_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        return $this->save($request, $csrf, new DiningTable());
    }

    #[Route('/{id}/edit', name: 'app_admin_table_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $table = $this->entityManager->getRepository(DiningTable::class)->find($id);
        if (!$table instanceof DiningTable) {
            throw $this->createNotFoundException('La mesa no existe.');
        }

        return $this->save($request, $csrf, $table);
    }

    private function save(Request $request, CsrfTokenManagerInterface $csrf, DiningTable $table): Response
    {
        $error = null;
        if ($request->isMethod('POST')) {
            if (!$csrf->isTokenValid(new CsrfToken('admin_table', (string) $request->request->get('_token')))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $name = trim((string) $request->request->get('name'));
            $position = trim((string) $request->request->get('position'));
            $existing = $this->entityManager->getRepository(DiningTable::class)->findOneBy(['name' => $name]);
            if ($name === '') {
                $error = 'El nombre o número de mesa es obligatorio.';
            } elseif (!preg_match('/^\d+$/', $position)) {
                $error = 'El orden debe ser un número entero igual o mayor que cero.';
            } elseif ($existing instanceof DiningTable && $existing !== $table) {
                $error = 'Ya existe una mesa con ese nombre.';
            } else {
                $table->setName($name)
                    ->setPosition((int) $position)
                    ->setActive($request->request->getBoolean('active'));
                $this->entityManager->persist($table);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_admin_tables');
            }
        }

        return $this->render('admin/dining_table_form.html.twig', [
            'user' => $this->getUser(),
            'table' => $table,
            'new' => $table->getId() === null,
            'error' => $error,
        ]);
    }
}
