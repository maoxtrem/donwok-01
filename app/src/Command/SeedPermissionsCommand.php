<?php

namespace App\Command;

use App\Entity\Permission;
use App\Entity\PermissionGroup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:permissions:seed',
    description: 'Crea el catálogo inicial de permisos y grupos operativos.',
)]
final class SeedPermissionsCommand extends Command
{
    private const PERMISSIONS = [
        ['code' => 'dashboard.read', 'name' => 'Ver panel operativo', 'resource' => 'dashboard', 'action' => 'read'],
        ['code' => 'sale.read', 'name' => 'Ver menú de venta', 'resource' => 'sale', 'action' => 'read'],
        ['code' => 'sale.create', 'name' => 'Crear órdenes desde venta', 'resource' => 'sale', 'action' => 'create'],
        ['code' => 'orders.read', 'name' => 'Consultar órdenes', 'resource' => 'orders', 'action' => 'read'],
        ['code' => 'orders.create', 'name' => 'Crear órdenes', 'resource' => 'orders', 'action' => 'create'],
        ['code' => 'orders.update', 'name' => 'Editar órdenes no facturadas', 'resource' => 'orders', 'action' => 'update'],
        ['code' => 'orders.select_cashier', 'name' => 'Seleccionar cajero', 'resource' => 'orders', 'action' => 'select_cashier'],
        ['code' => 'orders.change_table', 'name' => 'Cambiar mesa', 'resource' => 'orders', 'action' => 'change_table'],
        ['code' => 'orders.deliver_table', 'name' => 'Marcar entrega en mesa', 'resource' => 'orders', 'action' => 'deliver_table'],
        ['code' => 'orders.upload_receipt', 'name' => 'Cargar comprobantes', 'resource' => 'orders', 'action' => 'upload_receipt'],
        ['code' => 'orders.print', 'name' => 'Imprimir comandas', 'resource' => 'orders', 'action' => 'print'],
        ['code' => 'orders.reprint', 'name' => 'Reimprimir comandas', 'resource' => 'orders', 'action' => 'reprint'],
        ['code' => 'order_components.read', 'name' => 'Ver componentes de órdenes', 'resource' => 'order_components', 'action' => 'read'],
        ['code' => 'order_components.create', 'name' => 'Agregar componentes', 'resource' => 'order_components', 'action' => 'create'],
        ['code' => 'order_components.replace', 'name' => 'Reemplazar componentes', 'resource' => 'order_components', 'action' => 'replace'],
        ['code' => 'order_components.delete', 'name' => 'Quitar componentes', 'resource' => 'order_components', 'action' => 'delete'],
        ['code' => 'catalog_products.read', 'name' => 'Consultar productos', 'resource' => 'catalog_products', 'action' => 'read'],
        ['code' => 'catalog_products.create', 'name' => 'Crear productos', 'resource' => 'catalog_products', 'action' => 'create'],
        ['code' => 'catalog_products.update', 'name' => 'Actualizar productos', 'resource' => 'catalog_products', 'action' => 'update'],
        ['code' => 'catalog_products.delete', 'name' => 'Desactivar productos', 'resource' => 'catalog_products', 'action' => 'delete'],
        ['code' => 'catalog_composites.read', 'name' => 'Consultar compuestos', 'resource' => 'catalog_composites', 'action' => 'read'],
        ['code' => 'catalog_composites.create', 'name' => 'Crear compuestos', 'resource' => 'catalog_composites', 'action' => 'create'],
        ['code' => 'catalog_composites.update', 'name' => 'Actualizar compuestos', 'resource' => 'catalog_composites', 'action' => 'update'],
        ['code' => 'catalog_composites.delete', 'name' => 'Desactivar compuestos', 'resource' => 'catalog_composites', 'action' => 'delete'],
        ['code' => 'kitchen.read', 'name' => 'Ver comandas de cocina', 'resource' => 'kitchen', 'action' => 'read'],
        ['code' => 'kitchen.update_status', 'name' => 'Cambiar estado en cocina', 'resource' => 'kitchen', 'action' => 'update_status'],
        ['code' => 'kitchen.reject', 'name' => 'Rechazar comanda con motivo', 'resource' => 'kitchen', 'action' => 'reject'],
        ['code' => 'billing.read', 'name' => 'Consultar facturación', 'resource' => 'billing', 'action' => 'read'],
        ['code' => 'billing.create', 'name' => 'Facturar órdenes', 'resource' => 'billing', 'action' => 'create'],
        ['code' => 'billing.cancel', 'name' => 'Anular facturas', 'resource' => 'billing', 'action' => 'cancel'],
        ['code' => 'cash.read', 'name' => 'Consultar cajas y movimientos', 'resource' => 'cash', 'action' => 'read'],
        ['code' => 'cash.activate', 'name' => 'Activar caja', 'resource' => 'cash', 'action' => 'activate'],
        ['code' => 'cash.deactivate', 'name' => 'Desactivar caja', 'resource' => 'cash', 'action' => 'deactivate'],
        ['code' => 'cash.egress', 'name' => 'Registrar egresos', 'resource' => 'cash', 'action' => 'egress'],
        ['code' => 'cash.transfer', 'name' => 'Trasladar dinero entre cajas', 'resource' => 'cash', 'action' => 'transfer'],
        ['code' => 'cash.audit', 'name' => 'Arqueo de caja', 'resource' => 'cash', 'action' => 'audit'],
        ['code' => 'cash.close', 'name' => 'Cerrar caja', 'resource' => 'cash', 'action' => 'close'],
        ['code' => 'cash.reprint_close', 'name' => 'Reimprimir cierre de caja', 'resource' => 'cash', 'action' => 'reprint_close'],
        ['code' => 'cash.ledger.read', 'name' => 'Consultar libro mayor', 'resource' => 'cash', 'action' => 'ledger_read'],
        ['code' => 'cash.ledger.approve', 'name' => 'Aprobar egresos', 'resource' => 'cash', 'action' => 'ledger_approve'],
        ['code' => 'cash.ledger.read_own', 'name' => 'Consultar movimientos propios', 'resource' => 'cash', 'action' => 'ledger_read_own'],
        ['code' => 'cash.ledger.read_own', 'name' => 'Consultar movimientos propios', 'resource' => 'cash', 'action' => 'ledger_read_own'],
        ['code' => 'groups.read', 'name' => 'Consultar grupos y permisos', 'resource' => 'groups', 'action' => 'read'],
        ['code' => 'groups.create', 'name' => 'Crear grupos', 'resource' => 'groups', 'action' => 'create'],
        ['code' => 'groups.update', 'name' => 'Actualizar grupos y permisos', 'resource' => 'groups', 'action' => 'update'],
        ['code' => 'groups.delete', 'name' => 'Desactivar grupos', 'resource' => 'groups', 'action' => 'delete'],
        ['code' => 'users.read', 'name' => 'Consultar usuarios', 'resource' => 'users', 'action' => 'read'],
        ['code' => 'users.create', 'name' => 'Crear usuarios', 'resource' => 'users', 'action' => 'create'],
        ['code' => 'users.update', 'name' => 'Actualizar usuarios', 'resource' => 'users', 'action' => 'update'],
        ['code' => 'users.delete', 'name' => 'Desactivar usuarios', 'resource' => 'users', 'action' => 'delete'],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $permissions = [];
        $permissionRepository = $this->entityManager->getRepository(Permission::class);

        foreach (self::PERMISSIONS as $data) {
            $permission = $permissionRepository->findOneBy(['code' => $data['code']]);

            if (!$permission instanceof Permission) {
                $permission = (new Permission())
                    ->setCode($data['code'])
                    ->setName($data['name'])
                    ->setResource($data['resource'])
                    ->setAction($data['action']);
                $this->entityManager->persist($permission);
                $output->writeln(sprintf('<info>Permiso creado:</info> %s', $data['code']));
            }

            $permissions[$data['code']] = $permission;
        }

        $groupDefinitions = $this->groupDefinitions(array_keys($permissions));
        $userRepository = $this->entityManager->getRepository(User::class);
        $groupRepository = $this->entityManager->getRepository(PermissionGroup::class);

        foreach ($groupDefinitions as $name => $definition) {
            $group = $groupRepository->findOneBy(['name' => $name]);
            $created = false;

            if (!$group instanceof PermissionGroup) {
                $group = (new PermissionGroup())
                    ->setName($name)
                    ->setDescription($definition['description'])
                    ->setActive(true);
                $this->entityManager->persist($group);
                $created = true;
                $output->writeln(sprintf('<info>Grupo creado:</info> %s', $name));
            }

            if ($created) {
                foreach ($definition['permissions'] as $code) {
                    $group->addPermission($permissions[$code]);
                }
            }

            $user = $userRepository->findOneBy(['username' => $name]);
            if ($user instanceof User) {
                $user->addPermissionGroup($group);
            }
        }

        $this->entityManager->flush();
        $output->writeln('<info>Permisos y grupos iniciales sincronizados.</info>');

        return Command::SUCCESS;
    }

    /** @param list<string> $allPermissionCodes */
    private function groupDefinitions(array $allPermissionCodes): array
    {
        $mesero = [
            'dashboard.read', 'sale.read', 'sale.create', 'orders.read', 'orders.create',
            'orders.update', 'orders.select_cashier', 'orders.upload_receipt', 'orders.print',
            'orders.reprint', 'order_components.read', 'order_components.create',
            'order_components.replace', 'order_components.delete', 'orders.deliver_table',
        ];

        return [
            'supervisor' => [
                'description' => 'Administración operativa mediante permisos configurables.',
                'permissions' => $allPermissionCodes,
            ],
            'mesero' => [
                'description' => 'Creación y gestión de órdenes según el flujo del mesero.',
                'permissions' => $mesero,
            ],
            'cocinero' => [
                'description' => 'Consulta y actualización de comandas en cocina.',
                'permissions' => ['dashboard.read', 'kitchen.read', 'kitchen.update_status', 'kitchen.reject'],
            ],
            'cajero' => [
                'description' => 'Facturación y operación de cajas asignadas.',
                'permissions' => [
                    'dashboard.read', 'orders.read', 'orders.upload_receipt', 'billing.read',
                    'billing.create', 'cash.read', 'cash.activate', 'cash.deactivate',
                    'cash.egress', 'cash.transfer', 'cash.close', 'cash.ledger.read_own',
                ],
            ],
        ];
    }
}
