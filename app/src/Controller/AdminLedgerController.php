<?php

namespace App\Controller;

use App\Entity\CashLedgerEntry;
use App\Entity\CashBox;
use App\Entity\TemporaryCashBox;
use App\Entity\User;
use App\Security\PermissionChecker;
use App\Printing\PrintService;
use App\Service\FinancialTicketFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminLedgerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PermissionChecker $permissions,
        private readonly PrintService $printService,
        private readonly FinancialTicketFactory $ticketFactory,
    )
    {
    }

    #[Route('/libro-mayor', name: 'app_admin_ledger', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->permissions->isGrantedByGroup('cash.ledger.read')) {
            throw $this->createAccessDeniedException('No tienes permiso para consultar el libro mayor.');
        }

        return $this->render('admin/ledger.html.twig', [
            'user' => $this->getUser(),
            'entries' => $this->entityManager->getRepository(CashLedgerEntry::class)
                ->createQueryBuilder('entry')
                ->where('entry.status = :valid')
                ->setParameter('valid', 'validado')
                ->orderBy('entry.createdAt', 'DESC')
                ->setMaxResults(200)
                ->getQuery()
                ->getResult(),
            'pending_entries' => $this->entityManager->getRepository(CashLedgerEntry::class)
                ->createQueryBuilder('entry')
                ->where('entry.type = :type')
                ->andWhere('entry.status = :pending')
                ->setParameter('type', 'egreso')
                ->setParameter('pending', 'pendiente_revision')
                ->orderBy('entry.createdAt', 'ASC')
                ->getQuery()
                ->getResult(),
            'temporary_boxes' => $this->entityManager->getRepository(TemporaryCashBox::class)
                ->createQueryBuilder('temporary')
                ->orderBy('temporary.createdAt', 'DESC')
                ->getQuery()
                ->getResult(),
        ]);
    }

    #[Route('/api/libro-mayor/egresos/{id}/aprobar', name: 'api_admin_egress_approve', methods: ['POST'])]
    public function approveEgress(int $id): JsonResponse
    {
        if (!$this->permissions->isGrantedByGroup('cash.ledger.approve')) {
            return $this->json(['error' => 'No tienes permiso para aprobar egresos.'], Response::HTTP_FORBIDDEN);
        }

        $entry = $this->entityManager->getRepository(CashLedgerEntry::class)->find($id);
        $user = $this->getUser();
        if (!$entry instanceof CashLedgerEntry || $entry->getType() !== 'egreso') {
            return $this->json(['error' => 'El egreso no existe.'], Response::HTTP_NOT_FOUND);
        }
        if ($entry->getStatus() === 'validado') {
            return $this->json(['error' => 'Este egreso ya fue aprobado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($entry->getReceiptKey() === null) {
            return $this->json(['error' => 'No se puede aprobar un egreso sin comprobante.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$user instanceof User || !$entry->getCashBox() instanceof CashBox) {
            return $this->json(['error' => 'No se pudo identificar la caja del egreso.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $box = $entry->getCashBox();
        $amount = $this->toCents($this->negate($entry->getAmount()));
        if ($this->toCents($box->getBalance()) < $amount) {
            return $this->json(['error' => 'La caja no tiene saldo suficiente para aprobar este egreso.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $box->setBalance($this->fromCents($this->toCents($box->getBalance()) - $amount));
        $entry->setStatus('validado')->setUpdatedBy($user);
        $this->entityManager->flush();

        $message = 'Egreso aprobado y descontado de la caja.';
        try {
            $this->printService->print($this->ticketFactory->ledgerEntry($entry));
            $message .= ' Comprobante enviado a impresión.';
        } catch (\Throwable $exception) {
            error_log('Error imprimiendo egreso aprobado: '.$exception->getMessage());
            $message .= ' No fue posible imprimir el comprobante.';
        }

        return $this->json(['message' => $message]);
    }

    private function negate(string $value): string
    {
        return $this->fromCents(-$this->toCents($value));
    }

    private function toCents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-'), 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fromCents(int $value): string
    {
        return sprintf('%d.%02d', intdiv($value, 100), $value % 100);
    }
}
