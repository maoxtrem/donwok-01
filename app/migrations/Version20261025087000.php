<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025087000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sincroniza índices y tipos de fecha de puestos y turnos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_positions RENAME INDEX uniq_work_position_code TO UNIQ_B4974D2977153098');
        $this->addSql('ALTER TABLE work_shifts CHANGE startedAt startedAt DATETIME NOT NULL');
        $this->addSql('ALTER TABLE work_shifts CHANGE closedAt closedAt DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE work_shifts RENAME INDEX idx_work_shift_position TO IDX_B6E4B70DDD842E46');
        $this->addSql('ALTER TABLE work_shifts RENAME INDEX idx_work_shift_created_by TO IDX_B6E4B70D3174800F');
        $this->addSql('ALTER TABLE work_shifts RENAME INDEX idx_work_shift_updated_by TO IDX_B6E4B70D65FF1AEC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_shifts RENAME INDEX IDX_B6E4B70DDD842E46 TO idx_work_shift_position');
        $this->addSql('ALTER TABLE work_shifts RENAME INDEX IDX_B6E4B70D3174800F TO idx_work_shift_created_by');
        $this->addSql('ALTER TABLE work_shifts RENAME INDEX IDX_B6E4B70D65FF1AEC TO idx_work_shift_updated_by');
        $this->addSql('ALTER TABLE work_positions RENAME INDEX UNIQ_B4974D2977153098 TO uniq_work_position_code');
    }
}
