<?php

namespace App\Controller;

use App\Entity\CashMovement;
use App\Entity\Enum\OrderBillingStatus;
use App\Entity\Cashier;
use App\Entity\CashBox;
use App\Entity\Order;
use App\Entity\User;
use App\Security\PermissionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use InvalidArgumentException;

#[IsGranted('ROLE_USER')]
final class CashierController extends AbstractController
{
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
    public function finances(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('cash.read')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        $boxes = $cashier?->getBoxes()->toArray() ?? [];
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
            'invoiced_total' => $invoicedTotal,
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

        $this->entityManager->persist($movement);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Traslado registrado correctamente.',
            'sourceBalance' => $source->getBalance(),
            'destinationBalance' => $destination->getBalance(),
        ], Response::HTTP_CREATED);
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

    private function findModuleBox(Cashier $cashier, string $uuid): ?CashBox
    {
        foreach ($cashier->getBoxes() as $box) {
            if ($box->getUuid() === trim($uuid)) {
                return $box;
            }
        }

        return null;
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
