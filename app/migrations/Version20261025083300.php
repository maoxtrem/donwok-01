<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025083300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns the payment module UUID index name with Doctrine metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cashiers RENAME INDEX uniq_cashier_uuid TO UNIQ_EE44F633D17F50A6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cashiers RENAME INDEX UNIQ_EE44F633D17F50A6 TO uniq_cashier_uuid');
    }
}
