<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\OrderLockedException;
use App\Service\OrderEditor;
use App\Service\OrderLockService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderEditingController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/ordenes/{id}/editar', name: 'app_order_edit', methods: ['GET'])]
    public function edit(int $id, OrderLockService $lockService): Response
    {
        $order = $this->findOrder($id);
        $user = $this->currentUser();
        $error = null;

        try {
            $lockService->acquire($order, $user);
        } catch (OrderLockedException|InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }

        $response = $this->render('orders/edit.html.twig', [
            'order' => $order,
            'error' => $error,
            'can_edit' => $error === null,
            'products' => $this->entityManager->getRepository(Product::class)->findBy(['active' => true], ['name' => 'ASC']),
        ]);

        if ($error !== null) {
            $response->setStatusCode(Response::HTTP_LOCKED);
        }

        return $response;
    }

    #[Route('/api/orders/{id}', name: 'api_orders_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, OrderEditor $orderEditor): JsonResponse
    {
        $order = $this->findOrder($id);
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'El cuerpo de la solicitud debe ser JSON válido.'], 400);
        }

        try {
            $orderEditor->update($order, $this->currentUser(), $data);
        } catch (OrderLockedException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_LOCKED);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['id' => $order->getId(), 'updated' => true]);
    }

    #[Route('/api/orders/{id}/lock', name: 'api_orders_unlock', methods: ['DELETE'])]
    public function unlock(int $id, OrderLockService $lockService): JsonResponse
    {
        try {
            $lockService->release($this->findOrder($id), $this->currentUser());
        } catch (OrderLockedException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_LOCKED);
        }

        return $this->json(['released' => true]);
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
