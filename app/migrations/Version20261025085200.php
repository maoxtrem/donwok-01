<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a physical or digital money type to cash boxes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cash_boxes ADD moneyType VARCHAR(20) DEFAULT 'efectivo' NOT NULL");
        $this->addSql("UPDATE cash_boxes SET moneyType = CASE WHEN defaultPaymentMethod = 'digital' OR LOWER(name) IN ('digital', 'nequi') THEN 'digital' ELSE 'efectivo' END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes DROP moneyType');
    }
}
