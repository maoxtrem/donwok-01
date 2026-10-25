<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alinea los nombres de índices financieros con el mapeo Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX idx_4a0d4a5f5b8d6c7f TO IDX_DD9448DDCC8B49AA');
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX idx_4a0d4a5f1f1b9b5e TO IDX_DD9448DD4FE3326');
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX idx_4a0d4a5f3174800f TO IDX_DD9448DD3174800F');
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX idx_4a0d4a5f65ff1aec TO IDX_DD9448DD65FF1AEC');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX idx_1fc8a4a45b8d6c7f TO IDX_8F6001E866BD09B9');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX idx_1fc8a4a48d9f6d38 TO IDX_8F6001E88D9F6D38');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX idx_1fc8a4a4a3f4a8d2 TO IDX_8F6001E8229E70A7');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX idx_1fc8a4a43174800f TO IDX_8F6001E83174800F');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX idx_1fc8a4a465ff1aec TO IDX_8F6001E865FF1AEC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX IDX_DD9448DDCC8B49AA TO idx_4a0d4a5f5b8d6c7f');
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX IDX_DD9448DD4FE3326 TO idx_4a0d4a5f1f1b9b5e');
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX IDX_DD9448DD3174800F TO idx_4a0d4a5f3174800f');
        $this->addSql('ALTER TABLE cash_movements RENAME INDEX IDX_DD9448DD65FF1AEC TO idx_4a0d4a5f65ff1aec');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX IDX_8F6001E866BD09B9 TO idx_1fc8a4a45b8d6c7f');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX IDX_8F6001E88D9F6D38 TO idx_1fc8a4a48d9f6d38');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX IDX_8F6001E8229E70A7 TO idx_1fc8a4a4a3f4a8d2');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX IDX_8F6001E83174800F TO idx_1fc8a4a43174800f');
        $this->addSql('ALTER TABLE cash_ledger_entries RENAME INDEX IDX_8F6001E865FF1AEC TO idx_1fc8a4a465ff1aec');
    }
}
