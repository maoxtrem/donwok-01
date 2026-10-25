<?php

namespace App\Controller;

use App\Security\PermissionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(PermissionChecker $permissions): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('dashboard/index.html.twig', [
            'user' => $this->getUser(),
            'can_sale' => $permissions->isGranted('sale.read') && $permissions->isGranted('sale.create'),
            'can_orders' => $permissions->isGranted('orders.read'),
            'can_kitchen' => $permissions->isGranted('kitchen.read'),
            'can_cash' => $permissions->isGranted('cash.read'),
            'can_cashier' => $permissions->isGranted('billing.read'),
            'can_receipts' => $permissions->isGranted('orders.upload_receipt'),
        ]);
    }
}
