<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega estado pendiente a los egresos hasta cargar comprobante';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cash_ledger_entries ADD status VARCHAR(20) NOT NULL DEFAULT 'validado'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_ledger_entries DROP status');
    }
}
