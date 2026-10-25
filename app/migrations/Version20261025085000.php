<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds default cash and digital routing to payment module boxes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes ADD defaultPaymentMethod VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes DROP defaultPaymentMethod');
    }
}
