<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes the duplicated zero-balance test box from the existing payment module.';
    }

    public function up(Schema $schema): void
    {
        // The initial module received an old Efectivo box and a new default Efectivo box.
        // Keep the newer default boxes plus the existing third test box (Nequi).
        $this->addSql("DELETE FROM cash_boxes WHERE id = 1 AND cashier_id = 2 AND name = 'Efectivo' AND balance = '0.00'");
    }

    public function down(Schema $schema): void
    {
        // This cleanup is intentionally not reversed: the removed row was a duplicate
        // zero-balance test record and has no financial movements.
    }
}
