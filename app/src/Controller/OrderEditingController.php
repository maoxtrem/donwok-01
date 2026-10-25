<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Cashier;
use App\Entity\Composite;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\OrderLockedException;
use App\Service\OrderEditor;
use App\Service\OrderLockService;
use App\Service\OrderRealtimePublisher;
use App\Security\PermissionChecker;
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
    public function edit(int $id, OrderLockService $lockService, PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('orders.update')) {
            throw $this->createAccessDeniedException();
        }

        $order = $this->findOrder($id);
        $user = $this->currentUser();
        $error = null;

        try {
            $lockService->acquire($order, $user);
        } catch (OrderLockedException|InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }

        $response = $this->render('sale/index.html.twig', [
            'editMode' => true,
            'editOrderId' => $order->getId(),
            'order' => $order,
            'error' => $error,
            'can_edit' => $error === null,
            'products' => $this->entityManager->getRepository(Product::class)->findBy(['active' => true], ['name' => 'ASC']),
            'composites' => $this->entityManager->getRepository(Composite::class)->findBy(['active' => true], ['name' => 'ASC']),
            'cashiers' => $this->entityManager->createQueryBuilder()
                ->select('u')
                ->distinct()
                ->from(User::class, 'u')
                ->innerJoin('u.cashier', 'c')
                ->innerJoin('c.boxes', 'cashBox')
                ->where('u.active = :active')
                ->andWhere('c.active = :cashierActive')
                ->setParameter('active', true)
                ->setParameter('cashierActive', true)
                ->orderBy('u.username', 'ASC')
                ->getQuery()
                ->getResult(),
            'initialCart' => array_map(static function ($item, int $position): array {
                return [
                    'type' => 'custom_composite',
                    'id' => (string) $item->getId(),
                    'name' => sprintf('Combo %d', $position + 1),
                    'comboNumber' => $position + 1,
                    'price' => (float) $item->getSalePrice(),
                    'quantity' => $item->getQuantity(),
                    'components' => array_map(static fn ($component): array => [
                        'productId' => $component->getSourceProductId(),
                        'name' => $component->getProductName(),
                        'measure' => $component->getQuantity(),
                        'unit' => $component->getUnit(),
                        'price' => (float) $component->getSalePrice(),
                    ], $item->getComponents()->toArray()),
                ];
            }, $order->getItems()->toArray(), array_keys($order->getItems()->toArray())),
        ]);

        if ($error !== null) {
            $response->setStatusCode(Response::HTTP_LOCKED);
        }

        return $response;
    }

    #[Route('/api/orders/{id}', name: 'api_orders_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, OrderEditor $orderEditor, OrderRealtimePublisher $realtimePublisher, PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('orders.update')) {
            return $this->json(['error' => 'No tienes permiso para editar órdenes.'], Response::HTTP_FORBIDDEN);
        }

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

        $realtimePublisher->orderChanged($order, 'order.updated');

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
