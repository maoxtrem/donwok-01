<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el catálogo de mesas del restaurante';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE dining_tables (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, position INT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_DINING_TABLE_NAME (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE dining_tables');
    }
}
