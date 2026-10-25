<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025085800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el registro de movimientos entre cajas.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cash_movements (id INT AUTO_INCREMENT NOT NULL, sourceBox_id INT NOT NULL, destinationBox_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(12, 2) NOT NULL, reason VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_4A0D4A5F5B8D6C7F (sourceBox_id), INDEX IDX_4A0D4A5F1F1B9B5E (destinationBox_id), INDEX IDX_4A0D4A5F3174800F (createdBy_id), INDEX IDX_4A0D4A5F65FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cash_movements ADD CONSTRAINT FK_4A0D4A5F5B8D6C7F FOREIGN KEY (sourceBox_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE cash_movements ADD CONSTRAINT FK_4A0D4A5F1F1B9B5E FOREIGN KEY (destinationBox_id) REFERENCES cash_boxes (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE cash_movements ADD CONSTRAINT FK_4A0D4A5F3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_movements ADD CONSTRAINT FK_4A0D4A5F65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cash_movements');
    }
}
