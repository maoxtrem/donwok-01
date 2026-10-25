<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025053239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE composite_components (id INT AUTO_INCREMENT NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit VARCHAR(30) NOT NULL, position INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, composite_id INT NOT NULL, product_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_7986700251952D53 (composite_id), INDEX IDX_798670024584665A (product_id), INDEX IDX_798670023174800F (createdBy_id), INDEX IDX_7986700265FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE composites (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, code VARCHAR(120) DEFAULT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(12, 2) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7E861CC077153098 (code), INDEX IDX_7E861CC03174800F (createdBy_id), INDEX IDX_7E861CC065FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, code VARCHAR(120) DEFAULT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(12, 2) NOT NULL, unit VARCHAR(30) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_B3BA5A5A77153098 (code), INDEX IDX_B3BA5A5A3174800F (createdBy_id), INDEX IDX_B3BA5A5A65FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE composite_components ADD CONSTRAINT FK_7986700251952D53 FOREIGN KEY (composite_id) REFERENCES composites (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE composite_components ADD CONSTRAINT FK_798670024584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE composite_components ADD CONSTRAINT FK_798670023174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE composite_components ADD CONSTRAINT FK_7986700265FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE composites ADD CONSTRAINT FK_7E861CC03174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE composites ADD CONSTRAINT FK_7E861CC065FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE composite_components DROP FOREIGN KEY FK_7986700251952D53');
        $this->addSql('ALTER TABLE composite_components DROP FOREIGN KEY FK_798670024584665A');
        $this->addSql('ALTER TABLE composite_components DROP FOREIGN KEY FK_798670023174800F');
        $this->addSql('ALTER TABLE composite_components DROP FOREIGN KEY FK_7986700265FF1AEC');
        $this->addSql('ALTER TABLE composites DROP FOREIGN KEY FK_7E861CC03174800F');
        $this->addSql('ALTER TABLE composites DROP FOREIGN KEY FK_7E861CC065FF1AEC');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A3174800F');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A65FF1AEC');
        $this->addSql('DROP TABLE composite_components');
        $this->addSql('DROP TABLE composites');
        $this->addSql('DROP TABLE products');
    }
}
