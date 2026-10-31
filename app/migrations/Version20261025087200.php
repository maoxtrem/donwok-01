<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025087200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite bloquear la personalización de componentes de compuestos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE composites ADD allowCustomization TINYINT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE composites DROP allowCustomization');
    }
}
