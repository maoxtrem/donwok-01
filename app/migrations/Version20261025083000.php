<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a unique UUID to cash boxes so names can be duplicated.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes ADD uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE cash_boxes SET uuid = UUID() WHERE uuid IS NULL');
        $this->addSql('ALTER TABLE cash_boxes MODIFY uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CASH_BOX_UUID ON cash_boxes (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CASH_BOX_UUID ON cash_boxes');
        $this->addSql('ALTER TABLE cash_boxes DROP uuid');
    }
}
