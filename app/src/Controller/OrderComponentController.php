<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderItemComponent;
use App\Entity\User;
use App\Service\OrderComponentEditor;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderComponentController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/orders/{orderId}/items/{itemId}/components', name: 'api_order_component_add', methods: ['POST'])]
    public function add(int $orderId, int $itemId, Request $request, OrderComponentEditor $editor): JsonResponse
    {
        try {
            $data = $this->decode($request);
            $component = $editor->add(
                $this->findOrder($orderId),
                $this->findItem($itemId),
                $this->currentUser(),
                $this->positiveInt($data['productId'] ?? null),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json(['id' => $component->getId(), 'added' => true], 201);
    }

    #[Route('/api/orders/{orderId}/items/{itemId}/components/{componentId}', name: 'api_order_component_update', methods: ['PATCH'])]
    public function update(int $orderId, int $itemId, int $componentId, Request $request, OrderComponentEditor $editor): JsonResponse
    {
        try {
            $data = $this->decode($request);
            $editor->replace(
                $this->findOrder($orderId),
                $this->findItem($itemId),
                $this->findComponent($componentId),
                $this->currentUser(),
                $this->positiveInt($data['productId'] ?? null),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json(['updated' => true]);
    }

    #[Route('/api/orders/{orderId}/items/{itemId}/components/{componentId}', name: 'api_order_component_remove', methods: ['DELETE'])]
    public function remove(int $orderId, int $itemId, int $componentId, OrderComponentEditor $editor): JsonResponse
    {
        try {
            $editor->remove($this->findOrder($orderId), $this->findItem($itemId), $this->findComponent($componentId), $this->currentUser());
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json(['removed' => true]);
    }

    private function decode(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('El cuerpo de la solicitud debe ser JSON válido.');
        }

        return $data;
    }

    private function positiveInt(mixed $value): int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($result === false) {
            throw new InvalidArgumentException('El identificador no es válido.');
        }

        return $result;
    }

    private function findOrder(int $id): Order
    {
        $order = $this->entityManager->getRepository(Order::class)->find($id);

        if (!$order instanceof Order) {
            throw new InvalidArgumentException('La orden no existe.');
        }

        return $order;
    }

    private function findItem(int $id): OrderItem
    {
        $item = $this->entityManager->getRepository(OrderItem::class)->find($id);

        if (!$item instanceof OrderItem) {
            throw new InvalidArgumentException('El ítem no existe.');
        }

        return $item;
    }

    private function findComponent(int $id): OrderItemComponent
    {
        $component = $this->entityManager->getRepository(OrderItemComponent::class)->find($id);

        if (!$component instanceof OrderItemComponent) {
            throw new InvalidArgumentException('El componente no existe.');
        }

        return $component;
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
