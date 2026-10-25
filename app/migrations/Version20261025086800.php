<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sincroniza los índices de las tablas de cierre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX uniq_temp_cash_uuid TO UNIQ_36C30086D17F50A6');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX idx_temp_cash_closure TO IDX_36C30086FA43E9DA');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX idx_temp_cash_source TO IDX_36C30086CC8B49AA');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX idx_temp_cash_created_by TO IDX_36C300863174800F');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX idx_temp_cash_updated_by TO IDX_36C3008665FF1AEC');
        $this->addSql('ALTER TABLE cash_closure_lines RENAME INDEX idx_cash_closure_line_closure TO IDX_2D94CE36FA43E9DA');
        $this->addSql('ALTER TABLE cash_closure_lines RENAME INDEX idx_cash_closure_line_box TO IDX_2D94CE3666BD09B9');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX idx_cash_closure_cashier TO IDX_8811DB092EDB0489');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX idx_cash_closure_closed_by TO IDX_8811DB092F80AB3E');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX idx_cash_closure_created_by TO IDX_8811DB093174800F');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX idx_cash_closure_updated_by TO IDX_8811DB0965FF1AEC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX UNIQ_36C30086D17F50A6 TO uniq_temp_cash_uuid');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX IDX_36C30086FA43E9DA TO idx_temp_cash_closure');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX IDX_36C30086CC8B49AA TO idx_temp_cash_source');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX IDX_36C300863174800F TO idx_temp_cash_created_by');
        $this->addSql('ALTER TABLE temporary_cash_boxes RENAME INDEX IDX_36C3008665FF1AEC TO idx_temp_cash_updated_by');
        $this->addSql('ALTER TABLE cash_closure_lines RENAME INDEX IDX_2D94CE36FA43E9DA TO idx_cash_closure_line_closure');
        $this->addSql('ALTER TABLE cash_closure_lines RENAME INDEX IDX_2D94CE3666BD09B9 TO idx_cash_closure_line_box');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX IDX_8811DB092EDB0489 TO idx_cash_closure_cashier');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX IDX_8811DB092F80AB3E TO idx_cash_closure_closed_by');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX IDX_8811DB093174800F TO idx_cash_closure_created_by');
        $this->addSql('ALTER TABLE cash_closures RENAME INDEX IDX_8811DB0965FF1AEC TO idx_cash_closure_updated_by');
    }
}
