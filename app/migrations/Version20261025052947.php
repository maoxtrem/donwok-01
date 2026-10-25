<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025052947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE permission_groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, description VARCHAR(255) DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_39B79E9B5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_permission_groups (permissiongroup_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C1A825B0BD0DD96D (permissiongroup_id), INDEX IDX_C1A825B0A76ED395 (user_id), PRIMARY KEY (permissiongroup_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE permission_group_permissions (permissiongroup_id INT NOT NULL, permission_id INT NOT NULL, INDEX IDX_A305715EBD0DD96D (permissiongroup_id), INDEX IDX_A305715EFED90CCA (permission_id), PRIMARY KEY (permissiongroup_id, permission_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE permissions (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(120) NOT NULL, name VARCHAR(180) NOT NULL, resource VARCHAR(180) NOT NULL, action VARCHAR(30) NOT NULL, UNIQUE INDEX UNIQ_2DEDCC6F77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_permission_groups ADD CONSTRAINT FK_C1A825B0BD0DD96D FOREIGN KEY (permissiongroup_id) REFERENCES permission_groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permission_groups ADD CONSTRAINT FK_C1A825B0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE permission_group_permissions ADD CONSTRAINT FK_A305715EBD0DD96D FOREIGN KEY (permissiongroup_id) REFERENCES permission_groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE permission_group_permissions ADD CONSTRAINT FK_A305715EFED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_permission_groups DROP FOREIGN KEY FK_C1A825B0BD0DD96D');
        $this->addSql('ALTER TABLE user_permission_groups DROP FOREIGN KEY FK_C1A825B0A76ED395');
        $this->addSql('ALTER TABLE permission_group_permissions DROP FOREIGN KEY FK_A305715EBD0DD96D');
        $this->addSql('ALTER TABLE permission_group_permissions DROP FOREIGN KEY FK_A305715EFED90CCA');
        $this->addSql('DROP TABLE permission_groups');
        $this->addSql('DROP TABLE user_permission_groups');
        $this->addSql('DROP TABLE permission_group_permissions');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE users');
    }
}
