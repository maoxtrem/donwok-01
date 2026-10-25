<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reconcilia saldos cero con pagos de órdenes ya facturadas.';
    }

    public function up(Schema $schema): void
    {
        // Solo completa cajas que todavía están en cero. Las cajas con saldo
        // existente pueden incluir transferencias o movimientos administrativos.
        $this->addSql(<<<'SQL'
            UPDATE cash_boxes cb
            INNER JOIN (
                SELECT opa.cashBox_id AS cash_box_id, SUM(opa.amount) AS invoiced_total
                FROM order_payment_allocations opa
                INNER JOIN orders o ON o.id = opa.order_id
                WHERE o.billingStatus = 'facturada'
                GROUP BY opa.cashBox_id
            ) totals ON totals.cash_box_id = cb.id
            SET cb.balance = totals.invoiced_total
            WHERE cb.balance = '0.00'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // No se revierte: el saldo corregido representa dinero ya facturado.
    }
}
