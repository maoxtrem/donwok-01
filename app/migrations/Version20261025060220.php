<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025060220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alinea los valores predeterminados de precios del catálogo.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE composites CHANGE salePrice salePrice NUMERIC(12, 2) DEFAULT '0.00' NOT NULL");
        $this->addSql("ALTER TABLE products CHANGE salePrice salePrice NUMERIC(12, 2) DEFAULT '0.00' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE composites CHANGE salePrice salePrice NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE salePrice salePrice NUMERIC(12, 2) NOT NULL');
    }
}
