<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite consultar el libro mayor únicamente al grupo administrativo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO permissions (code, name, resource, action, createdAt, updatedAt)\n            SELECT 'cash.ledger.read', 'Consultar libro mayor', 'cash', 'ledger_read', NOW(), NOW()\n            WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'cash.ledger.read')");
        $this->addSql("INSERT INTO permission_group_permissions (permissiongroup_id, permission_id)\n            SELECT pg.id, p.id\n            FROM permission_groups pg\n            CROSS JOIN permissions p\n            WHERE pg.name = 'supervisor' AND p.code = 'cash.ledger.read'\n              AND NOT EXISTS (\n                SELECT 1 FROM permission_group_permissions existing\n                WHERE existing.permissiongroup_id = pg.id AND existing.permission_id = p.id\n              )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE pgp FROM permission_group_permissions pgp\n            INNER JOIN permission_groups pg ON pg.id = pgp.permissiongroup_id\n            INNER JOIN permissions p ON p.id = pgp.permission_id\n            WHERE pg.name = 'supervisor' AND p.code = 'cash.ledger.read'");
        $this->addSql("DELETE FROM permissions WHERE code = 'cash.ledger.read'");
    }
}
