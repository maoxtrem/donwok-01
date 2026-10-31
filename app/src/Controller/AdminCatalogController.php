<?php

namespace App\Controller;

use App\Entity\Composite;
use App\Entity\CompositeComponent;
use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminCatalogController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/products', name: 'app_admin_products', methods: ['GET'])]
    public function products(): Response
    {
        return $this->render('admin/products.html.twig', [
            'user' => $this->getUser(),
            'products' => $this->entityManager->getRepository(Product::class)->findBy([], ['name' => 'ASC']),
            'categories' => $this->entityManager->getRepository(Category::class)->findBy(['active' => true], ['position' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/products/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function newProduct(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        return $this->saveProduct($request, $csrf, new Product(), true);
    }

    #[Route('/products/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function editProduct(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $product = $this->entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('El producto no existe.');
        }

        return $this->saveProduct($request, $csrf, $product, false);
    }

    #[Route('/products/{id}/deactivate', name: 'app_admin_product_deactivate', methods: ['POST'])]
    public function deactivateProduct(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $product = $this->entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('El producto no existe.');
        }
        $this->assertCsrf($csrf, 'admin_product_deactivate_'.$id, $request);
        $product->setActive(false);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_products');
    }

    #[Route('/composites', name: 'app_admin_composites', methods: ['GET'])]
    public function composites(): Response
    {
        return $this->render('admin/composites.html.twig', [
            'user' => $this->getUser(),
            'composites' => $this->entityManager->getRepository(Composite::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/composites/new', name: 'app_admin_composite_new', methods: ['GET', 'POST'])]
    public function newComposite(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        return $this->saveComposite($request, $csrf, new Composite(), true);
    }

    #[Route('/composites/{id}/edit', name: 'app_admin_composite_edit', methods: ['GET', 'POST'])]
    public function editComposite(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $composite = $this->entityManager->getRepository(Composite::class)->find($id);
        if (!$composite instanceof Composite) {
            throw $this->createNotFoundException('El compuesto no existe.');
        }

        return $this->saveComposite($request, $csrf, $composite, false);
    }

    #[Route('/composites/{id}/deactivate', name: 'app_admin_composite_deactivate', methods: ['POST'])]
    public function deactivateComposite(int $id, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $composite = $this->entityManager->getRepository(Composite::class)->find($id);
        if (!$composite instanceof Composite) {
            throw $this->createNotFoundException('El compuesto no existe.');
        }
        $this->assertCsrf($csrf, 'admin_composite_deactivate_'.$id, $request);
        $composite->setActive(false);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_composites');
    }

    private function saveProduct(Request $request, CsrfTokenManagerInterface $csrf, Product $product, bool $new): Response
    {
        $error = null;
        if ($request->isMethod('POST')) {
            $this->assertCsrf($csrf, 'admin_product', $request);
            $code = trim((string) $request->request->get('code')) ?: null;
            $existing = $code !== null ? $this->entityManager->getRepository(Product::class)->findOneBy(['code' => $code]) : null;

            if (trim((string) $request->request->get('name')) === '') {
                $error = 'El nombre del producto es obligatorio.';
            } elseif ($existing instanceof Product && $existing !== $product) {
                $error = 'El código del producto ya existe.';
            } elseif (!preg_match('/^\d+$/', trim((string) $request->request->get('measure'))) || (int) $request->request->get('measure') <= 0) {
                $error = 'La medida debe ser un número entero mayor que cero.';
            } elseif (!$this->validDecimal((string) $request->request->get('costPrice'), 2) || !$this->validDecimal((string) $request->request->get('salePrice'), 2)) {
                $error = 'El costo y el precio de venta deben ser valores monetarios válidos.';
            } else {
                $product
                    ->setName((string) $request->request->get('name'))
                    ->setCode($code)
                    ->setDescription(trim((string) $request->request->get('description')) ?: null)
                    ->setMeasure((int) $request->request->get('measure'))
                    ->setUnit((string) $request->request->get('unit'))
                    ->setCostPrice((string) $request->request->get('costPrice'))
                    ->setSalePrice((string) $request->request->get('salePrice'))
                    ->setCategory($this->findCategory($request->request->get('category')))
                    ->setActive($request->request->has('active'));
                $this->entityManager->persist($product);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_admin_products');
            }
        }

        return $this->render('admin/product_form.html.twig', [
            'user' => $this->getUser(),
            'product' => $product,
            'categories' => $this->entityManager->getRepository(Category::class)->findBy(['active' => true], ['position' => 'ASC', 'name' => 'ASC']),
            'new' => $new,
            'error' => $error,
        ]);
    }

    private function saveComposite(Request $request, CsrfTokenManagerInterface $csrf, Composite $composite, bool $new): Response
    {
        $products = $this->entityManager->getRepository(Product::class)->findBy(['active' => true], ['name' => 'ASC']);
        $categories = $this->entityManager->getRepository(Category::class)->findBy(['active' => true], ['position' => 'ASC', 'name' => 'ASC']);
        $error = null;
        if ($request->isMethod('POST')) {
            $this->assertCsrf($csrf, 'admin_composite', $request);
            $code = trim((string) $request->request->get('code')) ?: null;
            $existing = $code !== null ? $this->entityManager->getRepository(Composite::class)->findOneBy(['code' => $code]) : null;
            $selectedIds = array_values(array_filter(array_map('intval', $request->request->all('components'))));
            $selectedProducts = array_values(array_filter($products, static fn (Product $product): bool => in_array($product->getId(), $selectedIds, true)));

            if (trim((string) $request->request->get('name')) === '') {
                $error = 'El nombre del compuesto es obligatorio.';
            } elseif ($existing instanceof Composite && $existing !== $composite) {
                $error = 'El código del compuesto ya existe.';
            } elseif ($selectedProducts === []) {
                $error = 'Selecciona al menos un producto para formar el compuesto.';
            } else {
                $composite
                    ->setName((string) $request->request->get('name'))
                    ->setCode($code)
                    ->setDescription(trim((string) $request->request->get('description')) ?: null)
                    ->setCategory($this->findCategory($request->request->get('category')))
                    ->setAllowCustomization($request->request->has('allowCustomization'))
                    ->setActive($request->request->has('active'));

                foreach ($composite->getComponents()->toArray() as $component) {
                    $composite->removeComponent($component);
                }
                foreach ($selectedProducts as $position => $product) {
                    $composite->addComponent(
                        (new CompositeComponent())
                            ->setProduct($product)
                            ->setQuantity($product->getMeasure())
                            ->setUnit($product->getUnit())
                            ->setCostPrice($product->getCostPrice())
                            ->setSalePrice($product->getSalePrice())
                            ->setPosition($position),
                    );
                }
                $composite->refreshPricesFromComponents();
                $this->entityManager->persist($composite);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_admin_composites');
            }
        }

        return $this->render('admin/composite_form.html.twig', [
            'user' => $this->getUser(), 'composite' => $composite, 'products' => $products, 'categories' => $categories, 'new' => $new, 'error' => $error,
        ]);
    }

    private function assertCsrf(CsrfTokenManagerInterface $csrf, string $id, Request $request): void
    {
        if (!$csrf->isTokenValid(new CsrfToken($id, (string) $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }
    }

    private function findCategory(mixed $id): ?Category
    {
        if ($id === null || $id === '') {
            return null;
        }

        $category = $this->entityManager->getRepository(Category::class)->find((int) $id);
        if (!$category instanceof Category || !$category->isActive()) {
            throw $this->createNotFoundException('La categoría seleccionada no existe.');
        }

        return $category;
    }

    private function validDecimal(string $value, int $scale): bool
    {
        return preg_match('/^\d+(?:\.\d{1,'.$scale.'})?$/', trim($value)) === 1;
    }
}
