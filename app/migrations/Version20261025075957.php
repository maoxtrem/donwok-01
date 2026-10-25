<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025075957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders ADD paymentMethod VARCHAR(20) DEFAULT NULL, ADD deliveryFee NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, ADD cashAmount NUMERIC(12, 2) DEFAULT NULL, ADD digitalAmount NUMERIC(12, 2) DEFAULT NULL, ADD cashReceived NUMERIC(12, 2) DEFAULT NULL, ADD changeDue NUMERIC(12, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP paymentMethod, DROP deliveryFee, DROP cashAmount, DROP digitalAmount, DROP cashReceived, DROP changeDue');
    }
}
