<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega puestos y turnos de trabajo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE work_positions (id INT AUTO_INCREMENT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, title VARCHAR(150) NOT NULL, code VARCHAR(30) NOT NULL, accessKeyHash VARCHAR(64) NOT NULL, description LONGTEXT DEFAULT NULL, location VARCHAR(120) DEFAULT NULL, contractType VARCHAR(80) DEFAULT NULL, hourlyRate NUMERIC(12, 2) DEFAULT NULL, minimumDenomination INT DEFAULT 50 NOT NULL, activeDays JSON NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_WORK_POSITION_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE work_shifts (id INT AUTO_INCREMENT NOT NULL, position_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, startedAt DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', closedAt DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', hourlyRate NUMERIC(12, 2) NOT NULL, minimumDenomination INT NOT NULL, amountToPay NUMERIC(12, 2) DEFAULT NULL, closeCodeHash VARCHAR(64) NOT NULL, INDEX IDX_WORK_SHIFT_POSITION (position_id), INDEX IDX_WORK_SHIFT_CREATED_BY (createdBy_id), INDEX IDX_WORK_SHIFT_UPDATED_BY (updatedBy_id), PRIMARY KEY(id), CONSTRAINT FK_WORK_SHIFT_POSITION FOREIGN KEY (position_id) REFERENCES work_positions (id) ON DELETE RESTRICT, CONSTRAINT FK_WORK_SHIFT_CREATED_BY FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL, CONSTRAINT FK_WORK_SHIFT_UPDATED_BY FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE work_shifts');
        $this->addSql('DROP TABLE work_positions');
    }
}
