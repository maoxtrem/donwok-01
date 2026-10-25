<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds an explicit flag for orders waiting for a payment receipt.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD paymentReceiptRequested TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP paymentReceiptRequested');
    }
}
