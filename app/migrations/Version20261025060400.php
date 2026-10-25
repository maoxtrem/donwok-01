<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025060400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convierte las medidas de productos y componentes a valores enteros.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products CHANGE measure measure INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE composite_components CHANGE quantity quantity INT NOT NULL');
        $this->addSql('ALTER TABLE order_item_components CHANGE quantity quantity INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE products CHANGE measure measure NUMERIC(12, 3) DEFAULT '1.000' NOT NULL");
        $this->addSql("ALTER TABLE composite_components CHANGE quantity quantity NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE order_item_components CHANGE quantity quantity NUMERIC(12, 3) NOT NULL");
    }
}
