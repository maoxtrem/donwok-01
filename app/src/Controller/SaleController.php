<?php

namespace App\Controller;

use App\Entity\Composite;
use App\Entity\Cashier;
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

        $products = $this->entityManager->getRepository(Product::class)->findBy(
            ['active' => true],
            ['name' => 'ASC'],
        );

        return $this->render('sale/index.html.twig', [
            'editMode' => false,
            'editOrderId' => null,
            'order' => null,
            'error' => null,
            'can_edit' => true,
            'initialCart' => [],
            'products' => $products,
            'composites' => $this->entityManager->getRepository(Composite::class)->findBy(
                ['active' => true],
                ['name' => 'ASC'],
            ),
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
}
