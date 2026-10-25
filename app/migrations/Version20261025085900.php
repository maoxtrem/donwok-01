<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el libro mayor financiero y registra las ventas facturadas existentes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cash_ledger_entries (id INT AUTO_INCREMENT NOT NULL, cashBox_id INT NOT NULL, order_id INT DEFAULT NULL, movement_id INT DEFAULT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, type VARCHAR(30) NOT NULL, amount NUMERIC(12, 2) NOT NULL, description VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_1FC8A4A45B8D6C7F (cashBox_id), INDEX IDX_1FC8A4A48D9F6D38 (order_id), INDEX IDX_1FC8A4A4A3F4A8D2 (movement_id), INDEX IDX_1FC8A4A43174800F (createdBy_id), INDEX IDX_1FC8A4A465FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cash_ledger_entries ADD CONSTRAINT FK_1FC8A4A45B8D6C7F FOREIGN KEY (cashBox_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE cash_ledger_entries ADD CONSTRAINT FK_1FC8A4A48D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_ledger_entries ADD CONSTRAINT FK_1FC8A4A4A3F4A8D2 FOREIGN KEY (movement_id) REFERENCES cash_movements (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_ledger_entries ADD CONSTRAINT FK_1FC8A4A43174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_ledger_entries ADD CONSTRAINT FK_1FC8A4A465FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql(<<<'SQL'
            INSERT INTO cash_ledger_entries
                (cashBox_id, order_id, createdBy_id, updatedBy_id, type, amount, description, createdAt, updatedAt)
            SELECT opa.cashBox_id, opa.order_id, opa.createdBy_id, opa.updatedBy_id,
                   'venta', opa.amount,
                   CONCAT('Pago de orden #', o.orderNumber),
                   opa.createdAt, opa.updatedAt
            FROM order_payment_allocations opa
            INNER JOIN orders o ON o.id = opa.order_id
            WHERE o.billingStatus = 'facturada'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cash_ledger_entries');
    }
}
