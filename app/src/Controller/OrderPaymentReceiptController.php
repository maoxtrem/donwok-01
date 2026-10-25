<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Enum\OrderBillingStatus;
use App\Entity\User;
use App\Security\PermissionChecker;
use App\Service\MinioStorage;
use App\Service\OrderHistoryRecorder;
use App\Service\OrderRealtimePublisher;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

#[IsGranted('ROLE_USER')]
final class OrderPaymentReceiptController extends AbstractController
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/orders/{id}/payment-receipt', name: 'api_order_payment_receipt_upload', methods: ['POST'])]
    public function upload(int $id, Request $request, MinioStorage $storage, OrderHistoryRecorder $historyRecorder, OrderRealtimePublisher $realtimePublisher, PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('orders.upload_receipt')) {
            return $this->json(['error' => 'No tienes permiso para cargar comprobantes.'], Response::HTTP_FORBIDDEN);
        }

        $order = $this->findOrder($id);
        $user = $this->currentUser();

        if ($order->isInvoiced()) {
            return $this->json(['error' => 'No se puede reemplazar el comprobante después de facturar.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->files->get('receipt');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'Selecciona un comprobante válido.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (($file->getSize() ?? 0) > 5 * 1024 * 1024) {
            return $this->json(['error' => 'El comprobante no puede superar 5 MB.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $mimeType = $file->getMimeType() ?: '';
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            return $this->json(['error' => 'Solo se permiten imágenes JPG, PNG, WEBP o PDF.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $oldKey = $order->getPaymentReceiptKey();
        $key = sprintf('orders/%s/%d/receipt-%s.%s', $order->getOrderDate()->format('Y-m-d'), $order->getOrderNumber(), bin2hex(random_bytes(8)), self::ALLOWED_MIME_TYPES[$mimeType]);

        try {
            $storage->putReceipt($file, $key);
            if ($oldKey !== null) {
                $storage->delete($oldKey);
            }
        } catch (Throwable) {
            return $this->json(['error' => 'No fue posible guardar el comprobante en MinIO.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $order
            ->setPaymentReceiptKey($key)
            ->setPaymentReceiptOriginalName($file->getClientOriginalName())
            ->setPaymentReceiptMimeType($mimeType)
            ->setPaymentReceiptUploadedAt(new DateTimeImmutable())
            ->setPaymentReceiptUploadedBy($user)
            ->setUpdatedBy($user);
        $historyRecorder->record($order, $user, 'order.payment_receipt.uploaded', [
            'replaced' => $oldKey !== null,
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $mimeType,
            'size' => $file->getSize(),
        ]);
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, 'order.payment_receipt.uploaded');

        return $this->json(['uploaded' => true, 'originalName' => $file->getClientOriginalName()]);
    }

    #[Route('/comprobantes', name: 'app_payment_receipts', methods: ['GET'])]
    public function pending(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('orders.upload_receipt')) {
            throw $this->createAccessDeniedException();
        }

        $orders = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.paymentReceiptRequested = :requested')
            ->andWhere('o.billingStatus = :pending')
            ->andWhere('o.operationalStatus != :delivered')
            ->setParameter('requested', true)
            ->setParameter('pending', OrderBillingStatus::PENDING->value)
            ->setParameter('delivered', 'entregada')
            ->orderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.orderNumber', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('receipts/index.html.twig', ['orders' => $orders]);
    }

    #[Route('/api/orders/{id}/payment-receipt', name: 'api_order_payment_receipt_read', methods: ['GET'])]
    public function read(int $id, MinioStorage $storage): Response
    {
        $order = $this->findOrder($id);
        if ($order->getPaymentReceiptKey() === null) {
            throw $this->createNotFoundException('La orden no tiene comprobante.');
        }

        try {
            [$body, $mimeType] = $storage->read($order->getPaymentReceiptKey());
        } catch (Throwable) {
            throw $this->createNotFoundException('El comprobante no está disponible.');
        }

        return new Response($body, Response::HTTP_OK, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($order->getPaymentReceiptOriginalName() ?: 'comprobante').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function findOrder(int $id): Order
    {
        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('La orden no existe.');
        }
        return $order;
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }
}
