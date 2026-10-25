<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025060119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE composite_components ADD costPrice NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, ADD salePrice NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE composites ADD costPrice NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE price salePrice NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE order_item_components ADD salePrice NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE unitPrice costPrice NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD salePrice NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE unitPrice costPrice NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE products ADD costPrice NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE price salePrice NUMERIC(12, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE composite_components DROP costPrice, DROP salePrice');
        $this->addSql('ALTER TABLE composites CHANGE salePrice price NUMERIC(12, 2) NOT NULL, DROP costPrice');
        $this->addSql('ALTER TABLE order_item_components CHANGE costPrice unitPrice NUMERIC(12, 2) NOT NULL, DROP salePrice');
        $this->addSql('ALTER TABLE order_items CHANGE costPrice unitPrice NUMERIC(12, 2) NOT NULL, DROP salePrice');
        $this->addSql('ALTER TABLE products CHANGE salePrice price NUMERIC(12, 2) NOT NULL, DROP costPrice');
    }
}
