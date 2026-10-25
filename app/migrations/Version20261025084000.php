<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025084000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds payment allocation details by cash box to orders.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_payment_allocations (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, cash_box_id INT NOT NULL, method VARCHAR(20) NOT NULL, amount NUMERIC(12, 2) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_5A0E9BB28D9F6D38 (order_id), INDEX IDX_5A0E9BB2A6A2F3A0 (cash_box_id), INDEX IDX_5A0E9BB2B03A8386 (createdBy_id), INDEX IDX_5A0E9BB2D9A8B7E0 (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_payment_allocations ADD CONSTRAINT FK_5A0E9BB28D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_payment_allocations ADD CONSTRAINT FK_5A0E9BB2A6A2F3A0 FOREIGN KEY (cash_box_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE order_payment_allocations ADD CONSTRAINT FK_5A0E9BB2B03A8386 FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_payment_allocations ADD CONSTRAINT FK_5A0E9BB2D9A8B7E0 FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_payment_allocations');
    }
}
