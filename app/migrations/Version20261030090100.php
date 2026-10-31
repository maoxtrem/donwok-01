<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030090100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alinea el nombre del índice de plantillas con Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE print_templates RENAME INDEX UNIQ_PRINT_TEMPLATE_CODE TO UNIQ_1D4E549777153098');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE print_templates RENAME INDEX UNIQ_1D4E549777153098 TO UNIQ_PRINT_TEMPLATE_CODE');
    }
}
