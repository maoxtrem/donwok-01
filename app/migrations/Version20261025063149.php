<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025063149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_history (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(60) NOT NULL, previousStatus VARCHAR(30) DEFAULT NULL, newStatus VARCHAR(30) DEFAULT NULL, details JSON NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, order_id INT NOT NULL, performedBy_id INT DEFAULT NULL, INDEX IDX_D1C0D9008D9F6D38 (order_id), INDEX IDX_D1C0D900AA03708 (performedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_history ADD CONSTRAINT FK_D1C0D9008D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_history ADD CONSTRAINT FK_D1C0D900AA03708 FOREIGN KEY (performedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_history DROP FOREIGN KEY FK_D1C0D9008D9F6D38');
        $this->addSql('ALTER TABLE order_history DROP FOREIGN KEY FK_D1C0D900AA03708');
        $this->addSql('DROP TABLE order_history');
    }
}
