<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025081555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cash_boxes DROP FOREIGN KEY `FK_FECF29C52EDB0489`');
        $this->addSql('ALTER TABLE cash_boxes CHANGE cashier_id cashier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cash_boxes ADD CONSTRAINT FK_FECF29C52EDB0489 FOREIGN KEY (cashier_id) REFERENCES cashiers (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cash_boxes DROP FOREIGN KEY FK_FECF29C52EDB0489');
        $this->addSql('ALTER TABLE cash_boxes CHANGE cashier_id cashier_id INT NOT NULL');
        $this->addSql('ALTER TABLE cash_boxes ADD CONSTRAINT `FK_FECF29C52EDB0489` FOREIGN KEY (cashier_id) REFERENCES cashiers (id) ON UPDATE NO ACTION');
    }
}
