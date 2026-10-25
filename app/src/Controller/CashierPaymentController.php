<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Security\PermissionChecker;
use App\Service\CashierPaymentService;
use App\Service\OrderHistoryRecorder;
use App\Service\OrderRealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CashierPaymentController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/caja/orders/{id}/payment', name: 'api_cashier_payment_update', methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        PermissionChecker $permissions,
        CashierPaymentService $paymentService,
        OrderHistoryRecorder $historyRecorder,
        OrderRealtimePublisher $realtimePublisher,
    ): JsonResponse {
        if (!$permissions->isGranted('billing.create')) {
            return $this->json(['error' => 'No tienes permiso para validar pagos.'], 403);
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
        if ($order->isInvoiced()) {
            return $this->json(['error' => 'No se puede modificar el pago de una orden facturada.'], 422);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'El cuerpo de la solicitud debe ser JSON válido.'], 400);
        }

        try {
            $payment = $paymentService->apply($order, $cashier, $data);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        $historyRecorder->record($order, $cashier, 'order.payment_validated', [
            'paymentMethod' => $payment['method']->value,
            'deliveryFee' => $payment['deliveryFee'],
            'cashAmount' => $payment['cashAmount'],
            'digitalAmount' => $payment['digitalAmount'],
            'cashReceived' => $payment['cashReceived'],
            'changeDue' => $payment['changeDue'],
            'total' => $payment['total'],
            'allocations' => array_map(static fn ($allocation): array => [
                'cashBoxUuid' => $allocation->getCashBox()?->getUuid(),
                'amount' => $allocation->getAmount(),
                'method' => $allocation->getMethod()->value,
            ], $payment['allocations']),
        ]);
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, 'order.payment_validated');

        return $this->json([
            'orderId' => $order->getId(),
            'paymentMethod' => $payment['method']->value,
            'total' => $payment['total'],
            'changeDue' => $payment['changeDue'],
            'allocations' => array_map(static fn ($allocation): array => [
                'cashBoxUuid' => $allocation->getCashBox()?->getUuid(),
                'cashBoxName' => $allocation->getCashBox()?->getName(),
                'method' => $allocation->getMethod()->value,
                'amount' => $allocation->getAmount(),
            ], $payment['allocations']),
        ]);
    }

    #[Route('/api/caja/orders/{id}/payment-draft', name: 'api_cashier_payment_draft', methods: ['PATCH'])]
    public function draft(
        int $id,
        Request $request,
        PermissionChecker $permissions,
        CashierPaymentService $paymentService,
        OrderHistoryRecorder $historyRecorder,
        OrderRealtimePublisher $realtimePublisher,
    ): JsonResponse {
        if (!$permissions->isGranted('billing.create')) {
            return $this->json(['error' => 'No tienes permiso para configurar pagos.'], 403);
        }

        $cashier = $this->getUser();
        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$cashier instanceof User || !$order instanceof Order) {
            return $this->json(['error' => 'La orden no existe o la sesión no es válida.'], 404);
        }
        if (!$this->isGranted('ROLE_ADMIN') && $order->getAssignedCashier()?->getId() !== $cashier->getId()) {
            return $this->json(['error' => 'La orden no está asignada a este cajero.'], 403);
        }
        if ($order->isInvoiced()) {
            return $this->json(['error' => 'No se puede modificar una orden facturada.'], 422);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'El cuerpo de la solicitud debe ser JSON válido.'], 400);
        }

        try {
            $payment = $paymentService->saveDraft($order, $cashier, $data);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        $historyRecorder->record($order, $cashier, 'order.payment_draft_updated', [
            'cashAmount' => $payment['cashAmount'],
            'digitalAmount' => $payment['digitalAmount'],
            'deliveryFee' => $payment['deliveryFee'],
        ]);
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, 'order.payment_draft_updated');

        return $this->json([
            'saved' => true,
            'cashAmount' => $payment['cashAmount'],
            'digitalAmount' => $payment['digitalAmount'],
        ]);
    }

    #[Route('/api/caja/orders/{id}/receipt-request', name: 'api_cashier_receipt_request', methods: ['PATCH'])]
    public function receiptRequest(int $id, Request $request, PermissionChecker $permissions, OrderHistoryRecorder $historyRecorder, OrderRealtimePublisher $realtimePublisher): JsonResponse
    {
        if (!$permissions->isGranted('billing.create')) {
            return $this->json(['error' => 'No tienes permiso para solicitar comprobantes.'], 403);
        }

        $cashier = $this->getUser();
        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$cashier instanceof User || !$order instanceof Order) {
            return $this->json(['error' => 'La orden no existe o la sesión no es válida.'], 404);
        }
        if (!$this->isGranted('ROLE_ADMIN') && $order->getAssignedCashier()?->getId() !== $cashier->getId()) {
            return $this->json(['error' => 'La orden no está asignada a este cajero.'], 403);
        }
        if ($order->isInvoiced()) {
            return $this->json(['error' => 'No se puede cambiar una orden facturada.'], 422);
        }

        $data = json_decode($request->getContent(), true);
        $requested = is_array($data) && filter_var($data['requested'] ?? false, FILTER_VALIDATE_BOOL);
        $order->setPaymentReceiptRequested($requested)->setUpdatedBy($cashier);
        $historyRecorder->record($order, $cashier, $requested ? 'order.payment_receipt.requested' : 'order.payment_receipt.request_cancelled');
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, $requested ? 'order.payment_receipt.requested' : 'order.payment_receipt.request_cancelled');

        return $this->json(['requested' => $requested]);
    }
}
