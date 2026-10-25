<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025053140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE permission_groups ADD createdAt DATETIME NOT NULL, ADD updatedAt DATETIME NOT NULL, ADD createdBy_id INT DEFAULT NULL, ADD updatedBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE permission_groups ADD CONSTRAINT FK_39B79E9B3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE permission_groups ADD CONSTRAINT FK_39B79E9B65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_39B79E9B3174800F ON permission_groups (createdBy_id)');
        $this->addSql('CREATE INDEX IDX_39B79E9B65FF1AEC ON permission_groups (updatedBy_id)');
        $this->addSql('ALTER TABLE permissions ADD createdAt DATETIME NOT NULL, ADD updatedAt DATETIME NOT NULL, ADD createdBy_id INT DEFAULT NULL, ADD updatedBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE permissions ADD CONSTRAINT FK_2DEDCC6F3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE permissions ADD CONSTRAINT FK_2DEDCC6F65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2DEDCC6F3174800F ON permissions (createdBy_id)');
        $this->addSql('CREATE INDEX IDX_2DEDCC6F65FF1AEC ON permissions (updatedBy_id)');
        $this->addSql('ALTER TABLE users ADD createdAt DATETIME NOT NULL, ADD updatedAt DATETIME NOT NULL, ADD createdBy_id INT DEFAULT NULL, ADD updatedBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E93174800F FOREIGN KEY (createdBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E965FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E93174800F ON users (createdBy_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E965FF1AEC ON users (updatedBy_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE permission_groups DROP FOREIGN KEY FK_39B79E9B3174800F');
        $this->addSql('ALTER TABLE permission_groups DROP FOREIGN KEY FK_39B79E9B65FF1AEC');
        $this->addSql('DROP INDEX IDX_39B79E9B3174800F ON permission_groups');
        $this->addSql('DROP INDEX IDX_39B79E9B65FF1AEC ON permission_groups');
        $this->addSql('ALTER TABLE permission_groups DROP createdAt, DROP updatedAt, DROP createdBy_id, DROP updatedBy_id');
        $this->addSql('ALTER TABLE permissions DROP FOREIGN KEY FK_2DEDCC6F3174800F');
        $this->addSql('ALTER TABLE permissions DROP FOREIGN KEY FK_2DEDCC6F65FF1AEC');
        $this->addSql('DROP INDEX IDX_2DEDCC6F3174800F ON permissions');
        $this->addSql('DROP INDEX IDX_2DEDCC6F65FF1AEC ON permissions');
        $this->addSql('ALTER TABLE permissions DROP createdAt, DROP updatedAt, DROP createdBy_id, DROP updatedBy_id');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E93174800F');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E965FF1AEC');
        $this->addSql('DROP INDEX IDX_1483A5E93174800F ON users');
        $this->addSql('DROP INDEX IDX_1483A5E965FF1AEC ON users');
        $this->addSql('ALTER TABLE users DROP createdAt, DROP updatedAt, DROP createdBy_id, DROP updatedBy_id');
    }
}
