<?php

namespace App\Controller;

use App\Entity\Composite;
use App\Entity\Cashier;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Security\PermissionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SaleController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/venta', name: 'app_sale', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('sale.read')) {
            throw $this->createAccessDeniedException();
        }

        $products = array_values(array_filter($this->entityManager->getRepository(Product::class)->findBy(
            ['active' => true],
            ['name' => 'ASC'],
        ), static fn (Product $product): bool => $product->getCategory() !== null));
        $composites = array_values(array_filter($this->entityManager->getRepository(Composite::class)->findBy(
            ['active' => true],
            ['name' => 'ASC'],
        ), static fn (Composite $composite): bool => $composite->getCategory() !== null));
        $categories = array_values(array_filter(
            $this->entityManager->getRepository(Category::class)->findBy(['active' => true], ['position' => 'ASC', 'name' => 'ASC']),
            static fn (Category $category): bool => self::hasCategory($products, $category)
                || self::hasCategory($composites, $category),
        ));

        return $this->render('sale/index.html.twig', [
            'editMode' => false,
            'editOrderId' => null,
            'order' => null,
            'error' => null,
            'can_edit' => true,
            'initialCart' => [],
            'products' => $products,
            'composites' => $composites,
            'categories' => $categories,
            'cashiers' => $this->entityManager->createQueryBuilder()
                ->select('u')
                ->distinct()
                ->from(User::class, 'u')
                ->innerJoin('u.cashier', 'c')
                ->innerJoin('c.boxes', 'cashBox')
                ->where('u.active = :active')
                ->andWhere('c.active = :cashierActive')
                ->setParameter('active', true)
                ->setParameter('cashierActive', true)
                ->orderBy('u.username', 'ASC')
                ->getQuery()
                ->getResult(),
        ]);
    }

    private static function hasCategory(array $items, Category $category): bool
    {
        foreach ($items as $item) {
            if ($item->getCategory()?->getId() === $category->getId()) {
                return true;
            }
        }

        return false;
    }

}
