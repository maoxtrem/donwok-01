<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025080157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, invoiceNumber INT NOT NULL, issuedAt DATETIME NOT NULL, paymentMethod VARCHAR(20) NOT NULL, subtotal NUMERIC(12, 2) NOT NULL, deliveryFee NUMERIC(12, 2) NOT NULL, total NUMERIC(12, 2) NOT NULL, cashAmount NUMERIC(12, 2) NOT NULL, digitalAmount NUMERIC(12, 2) NOT NULL, cashReceived NUMERIC(12, 2) DEFAULT NULL, changeDue NUMERIC(12, 2) DEFAULT NULL, itemsSnapshot JSON NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, order_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6A2F2F958D9F6D38 (order_id), UNIQUE INDEX uniq_invoices_number (invoiceNumber), INDEX IDX_6A2F2F953174800F (createdBy_id), INDEX IDX_6A2F2F9565FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F958D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F953174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F9565FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F958D9F6D38');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F953174800F');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F9565FF1AEC');
        $this->addSql('DROP TABLE invoices');
    }
}
