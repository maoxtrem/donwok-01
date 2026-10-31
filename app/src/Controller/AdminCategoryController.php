<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/categories')]
final class AdminCategoryController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'app_admin_categories', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/categories.html.twig', [
            'user' => $this->getUser(),
            'categories' => $this->entityManager->getRepository(Category::class)->findBy([], ['position' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        return $this->save($request, $csrf, new Category());
    }

    #[Route('/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$category instanceof Category) {
            throw $this->createNotFoundException('La categoría no existe.');
        }

        return $this->save($request, $csrf, $category);
    }

    private function save(Request $request, CsrfTokenManagerInterface $csrf, Category $category): Response
    {
        $error = null;
        if ($request->isMethod('POST')) {
            if (!$csrf->isTokenValid(new CsrfToken('admin_category', (string) $request->request->get('_token')))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $name = trim((string) $request->request->get('name'));
            $position = trim((string) $request->request->get('position'));
            $existing = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $name]);
            if ($name === '') {
                $error = 'El nombre de la categoría es obligatorio.';
            } elseif (!preg_match('/^\d+$/', $position)) {
                $error = 'El orden debe ser un número entero igual o mayor que cero.';
            } elseif ($existing instanceof Category && $existing !== $category) {
                $error = 'Ya existe una categoría con ese nombre.';
            } else {
                $defaultForSales = $request->request->getBoolean('defaultForSales');
                if ($defaultForSales) {
                    foreach ($this->entityManager->getRepository(Category::class)->findAll() as $otherCategory) {
                        if ($otherCategory !== $category) {
                            $otherCategory->setDefaultForSales(false);
                        }
                    }
                }

                $category->setName($name)
                    ->setPosition((int) $position)
                    ->setDefaultForSales($defaultForSales);
                $this->entityManager->persist($category);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_admin_categories');
            }
        }

        return $this->render('admin/category_form.html.twig', [
            'user' => $this->getUser(),
            'category' => $category,
            'new' => $category->getId() === null,
            'error' => $error,
        ]);
    }
}
