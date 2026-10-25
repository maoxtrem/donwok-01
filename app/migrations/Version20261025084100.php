<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025084100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns payment allocation indexes and cash box column naming with Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_payment_allocations DROP FOREIGN KEY FK_5A0E9BB2A6A2F3A0');
        $this->addSql('DROP INDEX IDX_5A0E9BB2A6A2F3A0 ON order_payment_allocations');
        $this->addSql('ALTER TABLE order_payment_allocations CHANGE cash_box_id cashBox_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_payment_allocations ADD CONSTRAINT FK_F923AA5666BD09B9 FOREIGN KEY (cashBox_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_F923AA5666BD09B9 ON order_payment_allocations (cashBox_id)');
        $this->addSql('ALTER TABLE order_payment_allocations RENAME INDEX idx_5a0e9bb28d9f6d38 TO IDX_F923AA568D9F6D38');
        $this->addSql('ALTER TABLE order_payment_allocations RENAME INDEX idx_5a0e9bb2b03a8386 TO IDX_F923AA563174800F');
        $this->addSql('ALTER TABLE order_payment_allocations RENAME INDEX idx_5a0e9bb2d9a8b7e0 TO IDX_F923AA5665FF1AEC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_payment_allocations DROP FOREIGN KEY FK_F923AA5666BD09B9');
        $this->addSql('DROP INDEX IDX_F923AA5666BD09B9 ON order_payment_allocations');
        $this->addSql('ALTER TABLE order_payment_allocations CHANGE cashBox_id cash_box_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_payment_allocations ADD CONSTRAINT FK_5A0E9BB2A6A2F3A0 FOREIGN KEY (cash_box_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
    }
}
