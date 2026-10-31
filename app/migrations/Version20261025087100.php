<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025087100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega categorías al catálogo de productos y compuestos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, position INT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_3AF346685E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE products ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B3BA5A5A12469DE2 ON products (category_id)');
        $this->addSql('ALTER TABLE composites ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE composites ADD CONSTRAINT FK_5D9E6E6912469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7E861CC012469DE2 ON composites (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A12469DE2');
        $this->addSql('ALTER TABLE products DROP category_id');
        $this->addSql('ALTER TABLE composites DROP FOREIGN KEY FK_5D9E6E6912469DE2');
        $this->addSql('ALTER TABLE composites DROP category_id');
        $this->addSql('DROP TABLE categories');
    }
}
