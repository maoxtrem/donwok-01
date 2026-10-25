<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025060300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega la medida fija de cada producto.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE products ADD measure NUMERIC(12, 3) DEFAULT '1.000' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP measure');
    }
}
