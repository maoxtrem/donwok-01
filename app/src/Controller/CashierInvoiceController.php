<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\User;
use App\Security\PermissionChecker;
use App\Service\InvoiceCreator;
use App\Service\OrderHistoryRecorder;
use App\Service\OrderRealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CashierInvoiceController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/caja/orders/{id}/invoice', name: 'api_cashier_invoice_create', methods: ['POST'])]
    public function create(
        int $id,
        PermissionChecker $permissions,
        InvoiceCreator $invoiceCreator,
        OrderHistoryRecorder $historyRecorder,
        OrderRealtimePublisher $realtimePublisher,
    ): JsonResponse {
        if (!$permissions->isGranted('billing.create')) {
            return $this->json(['error' => 'No tienes permiso para facturar órdenes.'], 403);
        }

        $cashier = $this->getUser();
        if (!$cashier instanceof User) {
            return $this->json(['error' => 'La sesión no tiene un usuario válido.'], 401);
        }
        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order) {
            return $this->json(['error' => 'La orden no existe.'], 404);
        }
        if (!$this->isGranted('ROLE_ADMIN') && $order->getAssignedCashier()?->getId() !== $cashier->getId()) {
            return $this->json(['error' => 'La orden no está asignada a este cajero.'], 403);
        }

        try {
            $invoice = $invoiceCreator->create($order, $cashier);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        $historyRecorder->record($order, $cashier, 'order.invoiced', [
            'invoiceNumber' => $invoice->getInvoiceNumber(),
            'total' => $invoice->getTotal(),
        ]);
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, 'order.invoiced');

        return $this->json([
            'invoiceId' => $invoice->getId(),
            'invoiceNumber' => $invoice->getInvoiceNumber(),
            'orderNumber' => $order->getOrderNumber(),
            'total' => $invoice->getTotal(),
        ], 201);
    }
}
