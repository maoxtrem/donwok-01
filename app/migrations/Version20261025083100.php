<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025083100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns the cash box UUID index name with Doctrine metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes RENAME INDEX uniq_cash_box_uuid TO UNIQ_FECF29C5D17F50A6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_boxes RENAME INDEX UNIQ_FECF29C5D17F50A6 TO uniq_cash_box_uuid');
    }
}
