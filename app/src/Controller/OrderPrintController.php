<?php

namespace App\Controller;

use App\Entity\Order;
use App\Printing\PrintService;
use App\Security\PermissionChecker;
use App\Service\OrderTicketFactory;
use App\Service\PrintTemplateProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function print(int $id, PrintTemplateProvider $templates): Response
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
            'kitchenTemplate' => $templates->get('kitchen'),
            'customerTemplate' => $templates->get('customer'),
        ]);
    }

    #[Route('/api/ordenes/{id}/imprimir', name: 'api_order_print', methods: ['POST'])]
    public function sendToPrinter(
        int $id,
        PermissionChecker $permissions,
        PrintService $printService,
        OrderTicketFactory $ticketFactory,
    ): JsonResponse {
        if (!$permissions->isGranted('orders.print')) {
            throw $this->createAccessDeniedException();
        }

        $order = $this->entityManager->getRepository(Order::class)->find($id);

        if (!$order instanceof Order) {
            return $this->json(['message' => 'La orden no existe.'], 404);
        }

        try {
            $printService->printSequence([
                $ticketFactory->kitchen($order),
                $ticketFactory->customer($order),
            ]);
        } catch (\Throwable $exception) {
            return $this->json(['message' => $exception->getMessage()], 503);
        }

        return $this->json(['message' => 'Los tickets fueron enviados a la impresora.']);
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
