<?php

namespace App\Controller;

use App\Entity\WorkPosition;
use App\Entity\WorkShift;
use App\Printing\PrintService;
use App\Service\WorkShiftTicketFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/turnos')]
final class AdminWorkPositionController extends AbstractController
{
    public function __construct(
        private readonly PrintService $printService,
        private readonly WorkShiftTicketFactory $ticketFactory,
    ) {
    }

    #[Route('', name: 'app_admin_work_positions', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrf): Response
    {
        $error = null;
        if ($request->isMethod('POST')) {
            if (!$csrf->isTokenValid(new CsrfToken('admin_work_position', (string) $request->request->get('_token')))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }
            try {
                $position = new WorkPosition(
                    (string) $request->request->get('title'),
                    (string) $request->request->get('code'),
                    (string) $request->request->get('access_key'),
                    (string) $request->request->get('description'),
                    (string) $request->request->get('hourly_rate'),
                    (int) $request->request->get('minimum_denomination', 50),
                    $request->request->all('active_days'),
                );
                $entityManager->persist($position);
                $entityManager->flush();
                return $this->redirectToRoute('app_admin_work_positions');
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render('admin/work_positions.html.twig', [
            'user' => $this->getUser(),
            'positions' => $entityManager->getRepository(WorkPosition::class)->findBy([], ['createdAt' => 'DESC']),
            'active_shifts' => $entityManager->getRepository(WorkShift::class)->findBy(['closedAt' => null], ['startedAt' => 'ASC']),
            'error' => $error,
        ]);
    }

    #[Route('/{id}/estado', name: 'app_admin_work_position_status', methods: ['POST'])]
    public function status(int $id, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrf): Response
    {
        if (!$csrf->isTokenValid(new CsrfToken('admin_work_position_status_'.$id, (string) $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }
        $position = $entityManager->getRepository(WorkPosition::class)->find($id);
        if (!$position instanceof WorkPosition) {
            throw $this->createNotFoundException('El puesto no existe.');
        }
        $position->setActive(!$position->isActive());
        $entityManager->flush();
        return $this->redirectToRoute('app_admin_work_positions');
    }

    #[Route('/{id}', name: 'api_admin_work_position_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $position = $entityManager->getRepository(WorkPosition::class)->find($id);
        if (!$position instanceof WorkPosition) {
            return $this->json(['error' => 'El puesto de trabajo no existe.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'El cuerpo de la solicitud no es válido.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $position->update(
                (string) ($data['title'] ?? $position->getTitle()),
                (string) ($data['code'] ?? $position->getCode()),
                array_key_exists('access_key', $data) ? (string) $data['access_key'] : null,
                array_key_exists('description', $data) ? (string) $data['description'] : $position->getDescription(),
                array_key_exists('hourly_rate', $data) && $data['hourly_rate'] !== '' ? (string) $data['hourly_rate'] : $position->getHourlyRate(),
                (int) ($data['minimum_denomination'] ?? $position->getMinimumDenomination()),
                is_array($data['active_days'] ?? null) ? $data['active_days'] : $position->getActiveDays(),
            );
            $entityManager->flush();
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['message' => 'Puesto actualizado correctamente.']);
    }

    #[Route('/{id}/turno/cerrar', name: 'api_admin_work_shift_close', methods: ['POST'])]
    public function closeShift(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $position = $entityManager->getRepository(WorkPosition::class)->find($id);
        $shift = $position instanceof WorkPosition
            ? $entityManager->getRepository(WorkShift::class)->findOneBy(['position' => $position, 'closedAt' => null], ['startedAt' => 'DESC'])
            : null;
        if (!$shift instanceof WorkShift) {
            return $this->json(['error' => 'No hay un turno activo para este puesto.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $shift->closeByAdmin($this->getUser() instanceof \App\Entity\User ? $this->getUser() : null);
            $entityManager->flush();
            $this->printService->print($this->ticketFactory->closed($shift));
            $message = 'Turno cerrado por administración y comprobante enviado a impresión.';
        } catch (\Throwable $exception) {
            error_log('Error cerrando turno por administración: '.$exception->getMessage());
            $message = 'Turno cerrado por administración; no fue posible imprimir el comprobante.';
        }

        return $this->json([
            'message' => $message,
            'status' => 'closed',
            'amountToPay' => $shift->getAmountToPay(),
            'workedMinutes' => $shift->getWorkedMinutes(),
        ]);
    }
}
