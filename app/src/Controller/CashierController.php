<?php

namespace App\Controller;

use App\Entity\CashMovement;
use App\Entity\CashLedgerEntry;
use App\Entity\Enum\OrderBillingStatus;
use App\Entity\Cashier;
use App\Entity\CashBox;
use App\Entity\Order;
use App\Entity\User;
use App\Security\PermissionChecker;
use App\Service\MinioStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

#[IsGranted('ROLE_USER')]
final class CashierController extends AbstractController
{
    private const EGRESS_RECEIPT_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/caja', name: 'app_cashier', methods: ['GET'])]
    public function page(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('billing.read')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $cashier = $user->getCashier();
        $moduleActive = $cashier?->isActive() && $cashier->getBoxes()->count() > 0;

        return $this->render('cashier/index.html.twig', [
            'orders' => $moduleActive ? $this->findAssignedOrders($user) : [],
            'cashier' => $cashier,
            'module_active' => $moduleActive,
            'payment_boxes' => $cashier?->getBoxes()->toArray() ?? [],
        ]);
    }

    #[Route('/caja/finanzas', name: 'app_cashier_finances', methods: ['GET'])]
    public function finances(Request $request, PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('cash.read')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        $boxes = $cashier?->getBoxes()->toArray() ?? [];
        $today = new DateTimeImmutable('today');
        $dateFrom = $this->validDate($request->query->get('from')) ?? $today;
        $dateTo = $this->validDate($request->query->get('to')) ?? $dateFrom;
        if ($dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $dateToExclusive = $dateTo->modify('+1 day');
        $orders = $this->findInvoicedOrders($user);
        $movements = $boxes === [] ? [] : $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(CashMovement::class, 'm')
            ->where('m.sourceBox IN (:boxes) OR m.destinationBox IN (:boxes)')
            ->setParameter('boxes', $boxes)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
        $ledgerEntries = $permissions->isGrantedByGroup('cash.ledger.read_own') && $boxes !== []
            ? $this->entityManager->createQueryBuilder()
                ->select('entry')
                ->from(CashLedgerEntry::class, 'entry')
                ->where('entry.cashBox IN (:boxes)')
                ->andWhere('entry.createdAt >= :dateFrom')
                ->andWhere('entry.createdAt < :dateToExclusive')
                ->setParameter('boxes', $boxes)
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateToExclusive', $dateToExclusive)
                ->orderBy('entry.createdAt', 'DESC')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult()
            : [];
        $boxTotals = [];
        $balanceTotal = 0.0;

        foreach ($boxes as $box) {
            $balanceTotal += (float) $box->getBalance();
            $boxTotals[$box->getUuid()] = [
                'box' => $box,
                'invoiced' => 0.0,
            ];
        }

        $invoicedTotal = 0.0;
        foreach ($orders as $order) {
            $invoicedTotal += (float) $order->getPayableTotal();
            foreach ($order->getPaymentAllocations() as $allocation) {
                $box = $allocation->getCashBox();
                if ($box instanceof CashBox && isset($boxTotals[$box->getUuid()])) {
                    $boxTotals[$box->getUuid()]['invoiced'] += (float) $allocation->getAmount();
                }
            }
        }

        return $this->render('cashier/finances.html.twig', [
            'cashier' => $cashier,
            'boxes' => array_values($boxTotals),
            'balance_total' => $balanceTotal,
            'orders' => $orders,
            'movements' => $movements,
            'ledger_entries' => $ledgerEntries,
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $dateTo->format('Y-m-d'),
            'invoiced_total' => $invoicedTotal,
        ]);
    }

    #[Route('/egresos', name: 'app_cashier_egresses', methods: ['GET'])]
    public function egresses(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('cash.read')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        $isGlobal = $permissions->isGrantedByGroup('cash.ledger.read');
        $boxes = $cashier?->getBoxes()->toArray() ?? [];
        $entries = [];
        $query = $this->entityManager->createQueryBuilder()
            ->select('entry')
            ->from(CashLedgerEntry::class, 'entry')
            ->where('entry.type = :type')
            ->andWhere('entry.status = :status')
            ->setParameter('type', 'egreso')
            ->setParameter('status', 'pendiente_revision')
            ->orderBy('entry.createdAt', 'DESC')
            ->setMaxResults(100);

        if (!$isGlobal) {
            if ($boxes !== []) {
                $query->andWhere('entry.cashBox IN (:boxes)')
                    ->setParameter('boxes', $boxes);
            }
        }

        if ($isGlobal || $boxes !== []) {
            $entries = $query->getQuery()->getResult();
        }

        return $this->render('cashier/egresses.html.twig', [
            'entries' => $entries,
            'global_view' => $isGlobal,
        ]);
    }

    #[Route('/api/caja/finanzas/traslado', name: 'api_cashier_transfer', methods: ['POST'])]
    public function transfer(Request $request, PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('cash.transfer')) {
            return $this->json(['error' => 'No tienes permiso para trasladar dinero entre cajas.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        if (!$cashier instanceof Cashier || !$cashier->isActive()) {
            return $this->json(['error' => 'El módulo debe estar activo para trasladar dinero.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Los datos del traslado no son válidos.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $amount = $this->parseMoney($data['amount'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $source = $this->findModuleBox($cashier, (string) ($data['sourceBox'] ?? ''));
        $destination = $this->findModuleBox($cashier, (string) ($data['destinationBox'] ?? ''));
        $reason = trim((string) ($data['reason'] ?? ''));

        if (!$source instanceof CashBox || !$destination instanceof CashBox) {
            return $this->json(['error' => 'Selecciona cajas pertenecientes a tu módulo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($source === $destination) {
            return $this->json(['error' => 'La caja origen y destino deben ser diferentes.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($reason === '') {
            return $this->json(['error' => 'Indica el motivo del traslado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($this->compareMoney($source->getBalance(), $amount) < 0) {
            return $this->json(['error' => 'La caja origen no tiene saldo suficiente.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $source->setBalance($this->addMoney($source->getBalance(), $this->negateMoney($amount)));
        $destination->setBalance($this->addMoney($destination->getBalance(), $amount));
        $movement = (new CashMovement())
            ->setSourceBox($source)
            ->setDestinationBox($destination)
            ->setAmount($amount)
            ->setReason($reason)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);
        $sourceEntry = (new CashLedgerEntry())
            ->setCashBox($source)
            ->setType('traslado_salida')
            ->setAmount($this->negateMoney($amount))
            ->setDescription(sprintf('Traslado hacia %s: %s', $destination->getName(), $reason))
            ->setMovement($movement)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);
        $destinationEntry = (new CashLedgerEntry())
            ->setCashBox($destination)
            ->setType('traslado_entrada')
            ->setAmount($amount)
            ->setDescription(sprintf('Traslado desde %s: %s', $source->getName(), $reason))
            ->setMovement($movement)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);

        $this->entityManager->persist($movement);
        $this->entityManager->persist($sourceEntry);
        $this->entityManager->persist($destinationEntry);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Traslado registrado correctamente.',
            'sourceBalance' => $source->getBalance(),
            'destinationBalance' => $destination->getBalance(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/caja/egresos', name: 'api_cashier_egress_create', methods: ['POST'])]
    public function egress(Request $request, PermissionChecker $permissions, MinioStorage $storage): JsonResponse
    {
        if (!$permissions->isGranted('cash.egress')) {
            return $this->json(['error' => 'No tienes permiso para registrar egresos.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        if (!$cashier instanceof Cashier || !$cashier->isActive()) {
            return $this->json(['error' => 'El módulo debe estar activo para registrar un egreso.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $amount = $this->parseMoney($request->request->get('amount'));
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $box = $this->findModuleBox($cashier, (string) $request->request->get('boxUuid', ''));
        $reason = trim((string) $request->request->get('reason', ''));
        $file = $request->files->get('receipt');

        if (!$box instanceof CashBox) {
            return $this->json(['error' => 'Selecciona una caja de tu módulo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($reason === '') {
            return $this->json(['error' => 'Indica el motivo del egreso.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $entry = (new CashLedgerEntry())
            ->setCashBox($box)
            ->setType('egreso')
            ->setStatus('pendiente_revision')
            ->setAmount($this->negateMoney($amount))
            ->setDescription($reason)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $this->storeEgressReceipt($entry, $file, $storage);
                $this->entityManager->flush();
            } catch (InvalidArgumentException $exception) {
                return $this->json(['error' => $exception->getMessage(), 'pendingId' => $entry->getId()], Response::HTTP_UNPROCESSABLE_ENTITY);
            } catch (Throwable) {
                return $this->json(['error' => 'El egreso quedó pendiente porque no fue posible guardar el comprobante.', 'pendingId' => $entry->getId()], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            return $this->json(['message' => 'Comprobante cargado. El egreso quedó pendiente de revisión administrativa.', 'pendingId' => $entry->getId()], Response::HTTP_ACCEPTED);
        }

        return $this->json(['message' => 'Egreso guardado como pendiente. Carga el comprobante para validarlo.', 'pendingId' => $entry->getId()], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/caja/egresos/{id}/comprobante', name: 'api_cashier_egress_receipt_upload', methods: ['POST'])]
    public function uploadEgressReceipt(int $id, Request $request, PermissionChecker $permissions, MinioStorage $storage): JsonResponse
    {
        if (!$permissions->isGranted('cash.egress')) {
            return $this->json(['error' => 'No tienes permiso para validar egresos.'], Response::HTTP_FORBIDDEN);
        }

        $entry = $this->entityManager->getRepository(CashLedgerEntry::class)->find($id);
        $user = $this->currentUser();
        if (!$entry instanceof CashLedgerEntry || $entry->getType() !== 'egreso') {
            return $this->json(['error' => 'El egreso no existe.'], Response::HTTP_NOT_FOUND);
        }
        if (!$this->userCanViewBox($user, $entry->getCashBox())) {
            return $this->json(['error' => 'No puedes modificar este egreso.'], Response::HTTP_FORBIDDEN);
        }
        if ($entry->getStatus() === 'validado') {
            return $this->json(['error' => 'Este egreso ya fue validado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->files->get('receipt');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'Selecciona un comprobante válido.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->storeEgressReceipt($entry, $file, $storage);
            $this->entityManager->flush();
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable) {
            return $this->json(['error' => 'No fue posible guardar el comprobante.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['message' => 'Comprobante cargado. El egreso quedó pendiente de revisión administrativa.'], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/caja/egresos/{id}/validar', name: 'api_cashier_egress_validate', methods: ['POST'])]
    public function validateEgressByCashier(int $id, PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('cash.egress')) {
            return $this->json(['error' => 'No tienes permiso para validar egresos.'], Response::HTTP_FORBIDDEN);
        }

        $entry = $this->entityManager->getRepository(CashLedgerEntry::class)->find($id);
        $user = $this->currentUser();
        if (!$entry instanceof CashLedgerEntry || $entry->getType() !== 'egreso') {
            return $this->json(['error' => 'El egreso no existe.'], Response::HTTP_NOT_FOUND);
        }
        if (!$this->userCanViewBox($user, $entry->getCashBox())) {
            return $this->json(['error' => 'No puedes validar este egreso.'], Response::HTTP_FORBIDDEN);
        }
        if ($entry->getStatus() === 'validado') {
            return $this->json(['error' => 'Este egreso ya fue validado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($entry->getReceiptKey() === null) {
            return $this->json(['error' => 'Carga y revisa el comprobante antes de validar.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $box = $entry->getCashBox();
        $amount = $this->negateMoney($entry->getAmount());
        if (!$box instanceof CashBox || $this->compareMoney($box->getBalance(), $amount) < 0) {
            return $this->json(['error' => 'La caja no tiene saldo suficiente para validar este egreso.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $box->setBalance($this->addMoney($box->getBalance(), $entry->getAmount()));
        $entry->setStatus('validado')->setUpdatedBy($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Egreso revisado y validado correctamente.']);
    }

    #[Route('/api/caja/egresos/{id}/comprobante', name: 'api_cashier_egress_receipt_read', methods: ['GET'])]
    public function egressReceipt(int $id, MinioStorage $storage, PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('cash.read')) {
            throw $this->createAccessDeniedException();
        }

        $entry = $this->entityManager->getRepository(CashLedgerEntry::class)->find($id);
        $user = $this->currentUser();
        if (!$entry instanceof CashLedgerEntry || $entry->getType() !== 'egreso' || $entry->getReceiptKey() === null) {
            throw $this->createNotFoundException('El comprobante no existe.');
        }
        if (!$this->userCanViewBox($user, $entry->getCashBox())) {
            throw $this->createAccessDeniedException();
        }

        try {
            [$body, $mimeType] = $storage->read($entry->getReceiptKey());
        } catch (Throwable) {
            throw $this->createNotFoundException('El comprobante no está disponible.');
        }

        return new Response($body, Response::HTTP_OK, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($entry->getReceiptOriginalName() ?: 'comprobante-egreso').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    #[Route('/caja/activar', name: 'app_cashier_activate', methods: ['POST'])]
    public function activate(PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('cash.activate')) {
            return $this->json(['error' => 'No tienes permiso para activar el módulo.'], 403);
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        $boxes = $cashier?->getBoxes()->toArray() ?? [];
        if ($boxes === []) {
            return $this->json(['error' => 'Este módulo no tiene cajas asignadas.'], 422);
        }

        $cashier->setActive(true);
        $this->entityManager->flush();

        return $this->json(['active' => true, 'boxes' => array_map(static fn (CashBox $box): string => $box->getName(), $boxes)]);
    }

    #[Route('/caja/desactivar', name: 'app_cashier_deactivate', methods: ['POST'])]
    public function deactivate(PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('cash.deactivate')) {
            return $this->json(['error' => 'No tienes permiso para desactivar el módulo.'], 403);
        }

        $cashier = $this->currentUser()->getCashier();
        if (!$cashier instanceof Cashier || !$cashier->isActive()) {
            return $this->json(['error' => 'El módulo ya está inactivo.'], 422);
        }

        $cashier->setActive(false);
        $this->entityManager->flush();

        return $this->json(['active' => false]);
    }

    /** @return list<Order> */
    private function findAssignedOrders(User $user): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.assignedCashier = :cashier')
            ->andWhere('o.billingStatus IN (:billingStatuses)')
            ->setParameter('cashier', $user)
            ->setParameter('billingStatuses', [
                OrderBillingStatus::PENDING->value,
                OrderBillingStatus::INVOICED->value,
            ])
            ->orderBy('CASE WHEN o.billingStatus = :pendingBillingStatus THEN 0 ELSE 1 END', 'ASC')
            ->addOrderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.orderNumber', 'DESC')
            ->setParameter('pendingBillingStatus', OrderBillingStatus::PENDING->value)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Order> */
    private function findInvoicedOrders(User $user): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.assignedCashier = :cashier')
            ->andWhere('o.billingStatus = :invoiced')
            ->setParameter('cashier', $user)
            ->setParameter('invoiced', OrderBillingStatus::INVOICED->value)
            ->orderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.orderNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function validDate(?string $value): ?DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }

    private function findModuleBox(Cashier $cashier, string $uuid): ?CashBox
    {
        foreach ($cashier->getBoxes() as $box) {
            if ($box->getUuid() === trim($uuid)) {
                return $box;
            }
        }

        return null;
    }

    private function userCanViewBox(User $user, ?CashBox $box): bool
    {
        if (!$box instanceof CashBox) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $cashier = $user->getCashier();
        if ($cashier instanceof Cashier && $cashier->getBoxes()->contains($box)) {
            return true;
        }

        foreach ($user->getPermissionGroups() as $group) {
            if (!$group->isActive()) {
                continue;
            }
            foreach ($group->getPermissions() as $permission) {
                if ($permission->getCode() === 'cash.ledger.read') {
                    return true;
                }
            }
        }

        return false;
    }

    private function storeEgressReceipt(CashLedgerEntry $entry, UploadedFile $file, MinioStorage $storage): void
    {
        if (($file->getSize() ?? 0) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('El comprobante no puede superar 5 MB.');
        }
        $mimeType = $file->getMimeType() ?: '';
        if (!isset(self::EGRESS_RECEIPT_MIME_TYPES[$mimeType])) {
            throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG, WEBP o PDF.');
        }

        $key = sprintf('cash-egress/%s/%s.%s', date('Y-m-d'), bin2hex(random_bytes(12)), self::EGRESS_RECEIPT_MIME_TYPES[$mimeType]);
        $storage->putReceipt($file, $key);
        $entry
            ->setReceiptKey($key)
            ->setReceiptOriginalName($file->getClientOriginalName())
            ->setReceiptMimeType($mimeType);
    }

    private function parseMoney(mixed $value): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException('El monto no es válido.');
        }

        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value) || $this->compareMoney($value, '0.00') <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor que cero y tener máximo dos decimales.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return sprintf('%d.%02d', (int) $whole, (int) str_pad($fraction, 2, '0'));
    }

    private function addMoney(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
    }

    private function negateMoney(string $value): string
    {
        return $this->fromCents(-$this->toCents($value));
    }

    private function compareMoney(string $left, string $right): int
    {
        return $this->toCents($left) <=> $this->toCents($right);
    }

    private function toCents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fromCents(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        return $sign . sprintf('%d.%02d', intdiv($value, 100), $value % 100);
    }
}
