<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025083200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a unique UUID to payment modules so names can be duplicated.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cashiers ADD uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE cashiers SET uuid = UUID() WHERE uuid IS NULL');
        $this->addSql('ALTER TABLE cashiers MODIFY uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CASHIER_UUID ON cashiers (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CASHIER_UUID ON cashiers');
        $this->addSql('ALTER TABLE cashiers DROP uuid');
    }
}
