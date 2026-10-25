<?php

namespace App\Controller;

use App\Entity\WorkPosition;
use App\Entity\WorkShift;
use App\Printing\PrintService;
use App\Service\WorkShiftTicketFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class WorkShiftController extends AbstractController
{
    public function __construct(
        private readonly PrintService $printService,
        private readonly WorkShiftTicketFactory $ticketFactory,
    ) {
    }

    #[Route('/turnos', name: 'app_work_shifts', methods: ['GET'])]
    public function page(): Response
    {
        return $this->render('work_shifts/index.html.twig');
    }

    #[Route('/api/turnos/puestos', name: 'api_work_positions', methods: ['GET'])]
    #[Route('/api/turnos/puestos/publicos', name: 'api_public_work_positions', methods: ['GET'])]
    public function positions(EntityManagerInterface $entityManager): JsonResponse
    {
        $day = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'][(int) (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('w')];
        $positions = $entityManager->getRepository(WorkPosition::class)->findBy(['active' => true], ['createdAt' => 'DESC']);
        $shifts = $entityManager->getRepository(WorkShift::class);

        $result = array_map(function (WorkPosition $position) use ($day, $shifts): ?array {
            if ($position->getActiveDays() !== [] && !in_array($day, $position->getActiveDays(), true)) return null;
            $shift = $shifts->findOneBy(['position' => $position, 'closedAt' => null], ['startedAt' => 'DESC']);
            return [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'code' => $position->getCode(),
                'description' => $position->getDescription(),
                'hourlyRate' => $position->getHourlyRate(),
                'shift' => $shift ? [
                    'id' => $shift->getId(),
                    'startedAt' => $shift->getStartedAt()->format('Y-m-d H:i:s'),
                    'hourlyRate' => $shift->getHourlyRate(),
                ] : null,
            ];
        }, $positions);

        return $this->json(array_values(array_filter($result)));
    }

    #[Route('/api/turnos/puestos/{id}/turno', name: 'api_work_shift_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $position = $entityManager->getRepository(WorkPosition::class)->find($id);
        if (!$position instanceof WorkPosition || !$position->isActive()) {
            throw new BadRequestHttpException('El puesto de trabajo no está disponible.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $shift = $entityManager->getRepository(WorkShift::class)->findOneBy(['position' => $position, 'closedAt' => null], ['startedAt' => 'DESC']);
        $user = $this->getUser();

        if (!$shift instanceof WorkShift) {
            if ($position->getHourlyRate() === null || (float) $position->getHourlyRate() <= 0) {
                throw new BadRequestHttpException('Este puesto no tiene configurado un valor por hora.');
            }
            if (!$position->accessKeyIsValid((string) ($data['key'] ?? ''))) {
                throw new BadRequestHttpException('La clave del puesto no es válida.');
            }
            $shift = new WorkShift($position, $user instanceof \App\Entity\User ? $user : null);
            $entityManager->persist($shift);
            $entityManager->flush();

            $message = 'Turno iniciado correctamente.';
            try {
                $this->printService->print($this->ticketFactory->started($shift));
                $message .= ' Comprobante enviado a impresión.';
            } catch (\Throwable $exception) {
                error_log('Error imprimiendo inicio de turno: '.$exception->getMessage());
                $message .= ' No fue posible imprimir el comprobante.';
            }

            return $this->json([
                'message' => $message,
                'status' => 'active',
                'startedAt' => $shift->getStartedAt()->format('Y-m-d H:i:s'),
                'closeCode' => $shift->getPlainCloseCode(),
            ], Response::HTTP_CREATED);
        }

        try {
            $shift->close((string) ($data['code'] ?? ''), $user instanceof \App\Entity\User ? $user : null);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
        $entityManager->flush();

        $message = 'Turno cerrado correctamente.';
        try {
            $this->printService->print($this->ticketFactory->closed($shift));
            $message .= ' Comprobante enviado a impresión.';
        } catch (\Throwable $exception) {
            error_log('Error imprimiendo cierre de turno: '.$exception->getMessage());
            $message .= ' No fue posible imprimir el comprobante.';
        }

        return $this->json([
            'message' => $message,
            'status' => 'closed',
            'startedAt' => $shift->getStartedAt()->format('Y-m-d H:i:s'),
            'closedAt' => $shift->getClosedAt()?->format('Y-m-d H:i:s'),
            'workedMinutes' => $shift->getWorkedMinutes(),
            'amountToPay' => $shift->getAmountToPay(),
            'minimumDenomination' => $shift->getMinimumDenomination(),
        ]);
    }
}
