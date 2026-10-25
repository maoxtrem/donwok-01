<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sincroniza la columna de estado del libro mayor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_ledger_entries ALTER status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cash_ledger_entries ALTER status SET DEFAULT 'validado'");
    }
}
