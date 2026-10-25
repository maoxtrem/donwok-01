<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns the cash box money type column with the Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes ALTER moneyType DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cash_boxes ALTER moneyType SET DEFAULT 'efectivo'");
    }
}
