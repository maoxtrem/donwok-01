<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderPrintController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/ordenes/{id}/imprimir', name: 'app_order_print', methods: ['GET'])]
    public function print(int $id): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->find($id);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException('La orden no existe.');
        }

        $items = array_map(function ($item): array {
            return [
                'name' => $item->getName(),
                'quantity' => $item->getQuantity(),
                'total' => $item->getTotal(),
                'components' => $this->groupComponents($item->getComponents()->toArray()),
            ];
        }, $order->getItems()->toArray());

        return $this->render('orders/print.html.twig', [
            'order' => $order,
            'items' => $items,
        ]);
    }

    private function groupComponents(array $components): array
    {
        $grouped = [];

        foreach ($components as $component) {
            $key = sprintf('%s|%s|%d', $component->getProductName(), $component->getUnit(), $component->getQuantity());

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'productName' => $component->getProductName(),
                    'quantity' => 0,
                    'unit' => $component->getUnit(),
                    'salePrice' => (float) $component->getSalePrice(),
                    'count' => 0,
                    'total' => 0.0,
                ];
            }

            $grouped[$key]['quantity'] += $component->getQuantity();
            $grouped[$key]['count'] += 1;
            $grouped[$key]['total'] += (float) $component->getSalePrice();
        }

        return array_values($grouped);
    }
}
