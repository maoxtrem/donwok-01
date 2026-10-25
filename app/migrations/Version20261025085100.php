<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds default cash and digital boxes to existing payment modules.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO cash_boxes (cashier_id, name, balance, defaultPaymentMethod, uuid, createdAt, updatedAt) SELECT id, 'Efectivo', '0.00', 'efectivo', UUID(), NOW(), NOW() FROM cashiers");
        $this->addSql("INSERT INTO cash_boxes (cashier_id, name, balance, defaultPaymentMethod, uuid, createdAt, updatedAt) SELECT id, 'Digital', '0.00', 'digital', UUID(), NOW(), NOW() FROM cashiers");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM cash_boxes WHERE defaultPaymentMethod IN ('efectivo', 'digital') AND balance = '0.00'");
    }
}
