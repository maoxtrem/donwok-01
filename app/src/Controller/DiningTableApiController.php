<?php

namespace App\Controller;

use App\Entity\DiningTable;
use App\Security\PermissionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DiningTableApiController extends AbstractController
{
    #[Route('/api/mesas', name: 'api_dining_tables', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('sale.read')) {
            return $this->json(['error' => 'No tienes permiso para consultar las mesas.'], 403);
        }

        $tables = $entityManager->getRepository(DiningTable::class)->findBy(
            ['active' => true],
            ['position' => 'ASC', 'name' => 'ASC'],
        );

        return $this->json(array_map(static fn (DiningTable $table): array => [
            'id' => $table->getId(),
            'name' => $table->getName(),
            'position' => $table->getPosition(),
        ], $tables));
    }
}
