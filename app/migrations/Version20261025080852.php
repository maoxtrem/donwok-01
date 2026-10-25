<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025080852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cashiers DROP FOREIGN KEY `FK_EE44F633A76ED395`');
        $this->addSql('ALTER TABLE cashiers ADD name VARCHAR(120) DEFAULT \'Cajero\' NOT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cashiers ADD CONSTRAINT FK_EE44F633A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cashiers DROP FOREIGN KEY FK_EE44F633A76ED395');
        $this->addSql('ALTER TABLE cashiers DROP name, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE cashiers ADD CONSTRAINT `FK_EE44F633A76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION');
    }
}
