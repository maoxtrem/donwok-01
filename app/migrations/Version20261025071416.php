<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025071416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders ADD paymentReceiptKey VARCHAR(255) DEFAULT NULL, ADD paymentReceiptOriginalName VARCHAR(180) DEFAULT NULL, ADD paymentReceiptMimeType VARCHAR(100) DEFAULT NULL, ADD paymentReceiptUploadedAt DATETIME DEFAULT NULL, ADD paymentReceiptUploadedBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE827FB8B1 FOREIGN KEY (paymentReceiptUploadedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E52FFDEE827FB8B1 ON orders (paymentReceiptUploadedBy_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE827FB8B1');
        $this->addSql('DROP INDEX IDX_E52FFDEE827FB8B1 ON orders');
        $this->addSql('ALTER TABLE orders DROP paymentReceiptKey, DROP paymentReceiptOriginalName, DROP paymentReceiptMimeType, DROP paymentReceiptUploadedAt, DROP paymentReceiptUploadedBy_id');
    }
}
