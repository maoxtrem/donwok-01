<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025086700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea arqueos, cierres y cajas temporales de módulos de pago';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE cash_closures (id INT AUTO_INCREMENT NOT NULL, cashier_id INT NOT NULL, closedBy_id INT NOT NULL, status VARCHAR(20) NOT NULL, expectedTotal NUMERIC(12, 2) NOT NULL, countedTotal NUMERIC(12, 2) NOT NULL, differenceTotal NUMERIC(12, 2) NOT NULL, observations VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_CASH_CLOSURE_CASHIER (cashier_id), INDEX IDX_CASH_CLOSURE_CLOSED_BY (closedBy_id), INDEX IDX_CASH_CLOSURE_CREATED_BY (createdBy_id), INDEX IDX_CASH_CLOSURE_UPDATED_BY (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE cash_closure_lines (id INT AUTO_INCREMENT NOT NULL, closure_id INT NOT NULL, cashBox_id INT NOT NULL, expected NUMERIC(12, 2) NOT NULL, counted NUMERIC(12, 2) NOT NULL, difference NUMERIC(12, 2) NOT NULL, INDEX IDX_CASH_CLOSURE_LINE_CLOSURE (closure_id), INDEX IDX_CASH_CLOSURE_LINE_BOX (cashBox_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE temporary_cash_boxes (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, closure_id INT NOT NULL, sourceBox_id INT NOT NULL, name VARCHAR(120) NOT NULL, balance NUMERIC(12, 2) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_TEMP_CASH_UUID (uuid), INDEX IDX_TEMP_CASH_CLOSURE (closure_id), INDEX IDX_TEMP_CASH_SOURCE (sourceBox_id), INDEX IDX_TEMP_CASH_CREATED_BY (createdBy_id), INDEX IDX_TEMP_CASH_UPDATED_BY (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE cash_closures ADD CONSTRAINT FK_CASH_CLOSURE_CASHIER FOREIGN KEY (cashier_id) REFERENCES cashiers (id) ON DELETE RESTRICT, ADD CONSTRAINT FK_CASH_CLOSURE_CLOSED_BY FOREIGN KEY (closedBy_id) REFERENCES users (id) ON DELETE RESTRICT, ADD CONSTRAINT FK_CASH_CLOSURE_CREATED_BY FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL, ADD CONSTRAINT FK_CASH_CLOSURE_UPDATED_BY FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_closure_lines ADD CONSTRAINT FK_CASH_CLOSURE_LINE_CLOSURE FOREIGN KEY (closure_id) REFERENCES cash_closures (id) ON DELETE CASCADE, ADD CONSTRAINT FK_CASH_CLOSURE_LINE_BOX FOREIGN KEY (cashBox_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE temporary_cash_boxes ADD CONSTRAINT FK_TEMP_CASH_CLOSURE FOREIGN KEY (closure_id) REFERENCES cash_closures (id) ON DELETE RESTRICT, ADD CONSTRAINT FK_TEMP_CASH_SOURCE FOREIGN KEY (sourceBox_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT, ADD CONSTRAINT FK_TEMP_CASH_CREATED_BY FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL, ADD CONSTRAINT FK_TEMP_CASH_UPDATED_BY FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE temporary_cash_boxes');
        $this->addSql('DROP TABLE cash_closure_lines');
        $this->addSql('DROP TABLE cash_closures');
    }
}
