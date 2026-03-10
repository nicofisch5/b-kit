<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309202823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coach_championship (id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, championship_id VARCHAR(36) NOT NULL, INDEX IDX_1A5DE676A76ED395 (user_id), INDEX IDX_1A5DE67694DDBCE9 (championship_id), UNIQUE INDEX UNIQ_1A5DE676A76ED39594DDBCE9 (user_id, championship_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE coach_team (id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, team_id VARCHAR(36) NOT NULL, INDEX IDX_47DFC303A76ED395 (user_id), INDEX IDX_47DFC303296CD8AE (team_id), UNIQUE INDEX UNIQ_47DFC303A76ED395296CD8AE (user_id, team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C1EE637C989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL, organization_id VARCHAR(36) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE coach_championship ADD CONSTRAINT FK_1A5DE676A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_championship ADD CONSTRAINT FK_1A5DE67694DDBCE9 FOREIGN KEY (championship_id) REFERENCES championship (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_team ADD CONSTRAINT FK_47DFC303A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_team ADD CONSTRAINT FK_47DFC303296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE championship ADD organization_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD organization_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE season ADD organization_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE team ADD organization_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coach_championship DROP FOREIGN KEY FK_1A5DE676A76ED395');
        $this->addSql('ALTER TABLE coach_championship DROP FOREIGN KEY FK_1A5DE67694DDBCE9');
        $this->addSql('ALTER TABLE coach_team DROP FOREIGN KEY FK_47DFC303A76ED395');
        $this->addSql('ALTER TABLE coach_team DROP FOREIGN KEY FK_47DFC303296CD8AE');
        $this->addSql('DROP TABLE coach_championship');
        $this->addSql('DROP TABLE coach_team');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('ALTER TABLE championship DROP organization_id');
        $this->addSql('ALTER TABLE player DROP organization_id');
        $this->addSql('ALTER TABLE season DROP organization_id');
        $this->addSql('ALTER TABLE team DROP organization_id');
    }
}
