<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025053347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_item_components (id INT AUTO_INCREMENT NOT NULL, sourceProductId INT DEFAULT NULL, productName VARCHAR(180) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit VARCHAR(30) NOT NULL, unitPrice NUMERIC(12, 2) NOT NULL, position INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, orderItem_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_98F01EABE76E9C94 (orderItem_id), INDEX IDX_98F01EAB3174800F (createdBy_id), INDEX IDX_98F01EAB65FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, sourceType VARCHAR(20) NOT NULL, sourceId INT DEFAULT NULL, name VARCHAR(180) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unitPrice NUMERIC(12, 2) NOT NULL, total NUMERIC(12, 2) NOT NULL, position INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, order_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_62809DB08D9F6D38 (order_id), INDEX IDX_62809DB03174800F (createdBy_id), INDEX IDX_62809DB065FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, orderDate DATE NOT NULL, orderNumber INT NOT NULL, orderType VARCHAR(20) NOT NULL, operationalStatus VARCHAR(30) NOT NULL, billingStatus VARCHAR(20) NOT NULL, tableNumber VARCHAR(60) DEFAULT NULL, customerName VARCHAR(180) DEFAULT NULL, customerPhone VARCHAR(40) DEFAULT NULL, deliveryAddress VARCHAR(255) DEFAULT NULL, lockedAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, assignedCashier_id INT DEFAULT NULL, lockedBy_id INT DEFAULT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX uniq_orders_date_number (orderDate, orderNumber), INDEX IDX_E52FFDEE55BB13E6 (assignedCashier_id), INDEX IDX_E52FFDEE1E253D71 (lockedBy_id), INDEX IDX_E52FFDEE3174800F (createdBy_id), INDEX IDX_E52FFDEE65FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_item_components ADD CONSTRAINT FK_98F01EABE76E9C94 FOREIGN KEY (orderItem_id) REFERENCES order_items (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item_components ADD CONSTRAINT FK_98F01EAB3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_item_components ADD CONSTRAINT FK_98F01EAB65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB03174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB065FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE55BB13E6 FOREIGN KEY (assignedCashier_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE1E253D71 FOREIGN KEY (lockedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item_components DROP FOREIGN KEY FK_98F01EABE76E9C94');
        $this->addSql('ALTER TABLE order_item_components DROP FOREIGN KEY FK_98F01EAB3174800F');
        $this->addSql('ALTER TABLE order_item_components DROP FOREIGN KEY FK_98F01EAB65FF1AEC');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB03174800F');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB065FF1AEC');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE55BB13E6');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE1E253D71');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE3174800F');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE65FF1AEC');
        $this->addSql('DROP TABLE order_item_components');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
    }
}
