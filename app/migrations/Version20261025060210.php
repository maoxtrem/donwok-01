<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025060210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Conserva como precio de venta el valor histórico de los pedidos anteriores.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE order_items SET salePrice = costPrice, costPrice = '0.00' WHERE salePrice = '0.00'");
        $this->addSql("UPDATE order_item_components SET salePrice = costPrice, costPrice = '0.00' WHERE salePrice = '0.00'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE order_items SET costPrice = salePrice, salePrice = '0.00' WHERE costPrice = '0.00'");
        $this->addSql("UPDATE order_item_components SET costPrice = salePrice, salePrice = '0.00' WHERE costPrice = '0.00'");
    }
}
