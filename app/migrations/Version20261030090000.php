<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega plantillas configurables para impresión de cocina y cliente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE print_templates (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, label VARCHAR(80) NOT NULL, brand VARCHAR(100) NOT NULL, title VARCHAR(100) NOT NULL, footer VARCHAR(180) NOT NULL, showDate TINYINT DEFAULT 1 NOT NULL, showOrderNumber TINYINT DEFAULT 1 NOT NULL, showOrderType TINYINT DEFAULT 1 NOT NULL, showTableNumber TINYINT DEFAULT 1 NOT NULL, showCustomer TINYINT DEFAULT 1 NOT NULL, showDeliveryDetails TINYINT DEFAULT 1 NOT NULL, showComponents TINYINT DEFAULT 1 NOT NULL, showPrices TINYINT DEFAULT 0 NOT NULL, showTotal TINYINT DEFAULT 0 NOT NULL, showPaymentDetails TINYINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_PRINT_TEMPLATE_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO print_templates (code, label, brand, title, footer, showDate, showOrderNumber, showOrderType, showTableNumber, showCustomer, showDeliveryDetails, showComponents, showPrices, showTotal, showPaymentDetails) VALUES ('kitchen', 'Comanda de cocina', 'DONWOK', 'COMANDA COCINA', 'Estado: en preparación', 1, 1, 1, 1, 1, 0, 1, 0, 0, 0), ('customer', 'Ticket de cliente', 'DONWOK', 'DESPRENDIBLE CLIENTE', 'Gracias por tu compra.', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE print_templates');
    }
}
