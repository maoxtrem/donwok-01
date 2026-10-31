<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite definir la categoría predeterminada en Ventas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories ADD default_for_sales TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories DROP default_for_sales');
    }
}
