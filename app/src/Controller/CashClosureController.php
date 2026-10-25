<?php

namespace App\Controller;

use App\Entity\CashClosure;
use App\Entity\CashClosureLine;
use App\Entity\CashLedgerEntry;
use App\Entity\CashBox;
use App\Entity\Cashier;
use App\Entity\Order;
use App\Entity\Enum\OrderBillingStatus;
use App\Entity\TemporaryCashBox;
use App\Entity\User;
use App\Security\PermissionChecker;
use App\Printing\PrintService;
use App\Service\FinancialTicketFactory;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CashClosureController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrintService $printService,
        private readonly FinancialTicketFactory $ticketFactory,
    )
    {
    }

    #[Route('/caja/cierre', name: 'app_cashier_closure', methods: ['GET'])]
    public function page(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('cash.close')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        $boxes = $cashier?->getBoxes()->toArray() ?? [];
        $pendingOrders = $this->pendingOrders($user);
        $expectedTotal = '0.00';
        foreach ($boxes as $box) {
            $expectedTotal = $this->addMoney($expectedTotal, $box->getBalance());
        }

        return $this->render('cashier/closure.html.twig', [
            'cashier' => $cashier,
            'boxes' => $boxes,
            'pending_orders' => $pendingOrders,
            'expected_total' => $expectedTotal,
            'can_close' => $cashier instanceof Cashier && $cashier->isActive() && $boxes !== [] && $pendingOrders === [],
        ]);
    }

    #[Route('/api/caja/cierre', name: 'api_cashier_closure_create', methods: ['POST'])]
    public function close(Request $request, PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('cash.close')) {
            return $this->json(['error' => 'No tienes permiso para cerrar el módulo.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->currentUser();
        $cashier = $user->getCashier();
        if (!$cashier instanceof Cashier || !$cashier->isActive()) {
            return $this->json(['error' => 'El módulo debe estar activo para realizar el cierre.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pendingOrders = $this->pendingOrders($user);
        if ($pendingOrders !== []) {
            return $this->json([
                'error' => 'No puedes cerrar mientras existan órdenes sin facturar o sin entregar.',
                'pendingOrders' => array_map(static fn (Order $order): int => $order->getOrderNumber(), $pendingOrders),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !is_array($data['counts'] ?? null)) {
            return $this->json(['error' => 'Registra el valor contado de cada caja.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $closure = (new CashClosure())
            ->setCashier($cashier)
            ->setClosedBy($user)
            ->setObservations(isset($data['observations']) ? (string) $data['observations'] : null)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);
        $expectedTotal = 0;
        $countedTotal = 0;

        foreach ($cashier->getBoxes() as $box) {
            $uuid = $box->getUuid();
            if (!array_key_exists($uuid, $data['counts'])) {
                return $this->json(['error' => sprintf('Falta registrar el conteo de la caja %s.', $box->getName())], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            try {
                $counted = $this->parseMoney($data['counts'][$uuid]);
            } catch (InvalidArgumentException $exception) {
                return $this->json(['error' => sprintf('%s: %s', $box->getName(), $exception->getMessage())], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $expected = $box->getBalance();
            $difference = $this->addMoney($counted, $this->negateMoney($expected));
            $line = (new CashClosureLine())
                ->setCashBox($box)
                ->setExpected($expected)
                ->setCounted($counted)
                ->setDifference($difference);
            $closure->addLine($line);
            $expectedTotal += $this->toCents($expected);
            $countedTotal += $this->toCents($counted);
        }

        $differenceTotal = $countedTotal - $expectedTotal;
        $closure
            ->setExpectedTotal($this->fromCents($expectedTotal))
            ->setCountedTotal($this->fromCents($countedTotal))
            ->setDifferenceTotal($this->fromCents($differenceTotal));

        foreach ($cashier->getBoxes() as $box) {
            if ($this->compareMoney($box->getBalance(), '0.00') > 0) {
                $amount = $box->getBalance();
                $temporary = (new TemporaryCashBox())
                    ->setClosure($closure)
                    ->setSourceBox($box)
                    ->setName(sprintf('Temporal cierre #%s · %s', 'pendiente', $box->getName()))
                    ->setBalance($amount)
                    ->setCreatedBy($user)
                    ->setUpdatedBy($user);
                $this->entityManager->persist($temporary);
                $this->entityManager->persist(
                    (new CashLedgerEntry())
                        ->setCashBox($box)
                        ->setType('cierre_salida')
                        ->setAmount($this->negateMoney($amount))
                        ->setDescription('Saldo trasladado a caja temporal por cierre')
                        ->setCreatedBy($user)
                        ->setUpdatedBy($user)
                );
                $box->setBalance('0.00');
            }
        }

        $cashier->setActive(false);
        $this->entityManager->persist($closure);
        $this->entityManager->flush();

        $message = 'Cierre realizado correctamente. El módulo quedó inactivo.';
        try {
            $this->printService->print($this->ticketFactory->closure($closure));
            $message .= ' Comprobante enviado a impresión.';
        } catch (\Throwable $exception) {
            error_log('Error imprimiendo cierre de caja: '.$exception->getMessage());
            $message .= ' No fue posible imprimir el comprobante.';
        }

        return $this->json([
            'message' => $message,
            'closureId' => $closure->getId(),
            'expected' => $closure->getExpectedTotal(),
            'counted' => $closure->getCountedTotal(),
            'difference' => $closure->getDifferenceTotal(),
        ], Response::HTTP_CREATED);
    }

    /** @return list<Order> */
    private function pendingOrders(User $user): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.assignedCashier = :cashier')
            ->andWhere('(o.billingStatus = :pendingBilling OR o.operationalStatus != :delivered)')
            ->setParameter('cashier', $user)
            ->setParameter('pendingBilling', OrderBillingStatus::PENDING->value)
            ->setParameter('delivered', 'entregada')
            ->orderBy('o.orderNumber', 'ASC')
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

    private function parseMoney(mixed $value): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException('El monto no es válido.');
        }
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Usa un valor positivo con máximo dos decimales.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return sprintf('%d.%02d', (int) $whole, (int) str_pad($fraction, 2, '0'));
    }

    private function addMoney(string $left, string $right): string { return $this->fromCents($this->toCents($left) + $this->toCents($right)); }
    private function negateMoney(string $value): string { return $this->fromCents(-$this->toCents($value)); }
    private function compareMoney(string $left, string $right): int { return $this->toCents($left) <=> $this->toCents($right); }
    private function toCents(string $value): int
    {
        $negative = str_starts_with($value, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-'), 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        return $negative ? -$cents : $cents;
    }
    private function fromCents(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        return $sign . sprintf('%d.%02d', intdiv($value, 100), $value % 100);
    }
}
