<?php

namespace App\Controller;

use App\Entity\Composite;
use App\Entity\Product;
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
    public function index(): Response
    {
        return $this->render('sale/index.html.twig', [
            'products' => $this->entityManager->getRepository(Product::class)->findBy(
                ['active' => true],
                ['name' => 'ASC'],
            ),
            'composites' => $this->entityManager->getRepository(Composite::class)->findBy(
                ['active' => true],
                ['name' => 'ASC'],
            ),
        ]);
    }
}
