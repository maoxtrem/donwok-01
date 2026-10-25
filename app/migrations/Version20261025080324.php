<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025080324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cash_boxes (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, type VARCHAR(30) NOT NULL, active TINYINT DEFAULT 0 NOT NULL, activatedAt DATETIME DEFAULT NULL, deactivatedAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, cashier_id INT NOT NULL, INDEX IDX_FECF29C52EDB0489 (cashier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cashiers (id INT AUTO_INCREMENT NOT NULL, active TINYINT DEFAULT 1 NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, user_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_EE44F633A76ED395 (user_id), INDEX IDX_EE44F6333174800F (createdBy_id), INDEX IDX_EE44F63365FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cash_boxes ADD CONSTRAINT FK_FECF29C52EDB0489 FOREIGN KEY (cashier_id) REFERENCES cashiers (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE cashiers ADD CONSTRAINT FK_EE44F633A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE cashiers ADD CONSTRAINT FK_EE44F6333174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cashiers ADD CONSTRAINT FK_EE44F63365FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cash_boxes DROP FOREIGN KEY FK_FECF29C52EDB0489');
        $this->addSql('ALTER TABLE cashiers DROP FOREIGN KEY FK_EE44F633A76ED395');
        $this->addSql('ALTER TABLE cashiers DROP FOREIGN KEY FK_EE44F6333174800F');
        $this->addSql('ALTER TABLE cashiers DROP FOREIGN KEY FK_EE44F63365FF1AEC');
        $this->addSql('DROP TABLE cash_boxes');
        $this->addSql('DROP TABLE cashiers');
    }
}
