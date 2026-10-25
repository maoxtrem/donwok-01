<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega comprobante obligatorio a los egresos del libro mayor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_ledger_entries ADD receiptKey VARCHAR(255) DEFAULT NULL, ADD receiptOriginalName VARCHAR(255) DEFAULT NULL, ADD receiptMimeType VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_ledger_entries DROP receiptKey, DROP receiptOriginalName, DROP receiptMimeType');
    }
}
