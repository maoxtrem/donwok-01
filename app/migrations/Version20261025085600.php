<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Asigna el permiso de entrega a los grupos mesero y cajero.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO permission_group_permissions (permissiongroup_id, permission_id)
            SELECT pg.id, p.id
            FROM permission_groups pg
            CROSS JOIN permissions p
            WHERE pg.name IN ('mesero', 'cajero')
              AND p.code = 'orders.deliver_table'
              AND NOT EXISTS (
                  SELECT 1
                  FROM permission_group_permissions existing
                  WHERE existing.permissiongroup_id = pg.id
                    AND existing.permission_id = p.id
              )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE assignments
            FROM permission_group_permissions assignments
            INNER JOIN permission_groups pg ON pg.id = assignments.permissiongroup_id
            INNER JOIN permissions p ON p.id = assignments.permission_id
            WHERE pg.name IN ('mesero', 'cajero')
              AND p.code = 'orders.deliver_table'
        SQL);
    }
}
