<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309160617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE championship (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE championship_season (id VARCHAR(36) NOT NULL, championship_id VARCHAR(36) NOT NULL, season_id VARCHAR(36) NOT NULL, INDEX IDX_87EC4FC094DDBCE9 (championship_id), INDEX IDX_87EC4FC04EC001D1 (season_id), UNIQUE INDEX uniq_championship_season (championship_id, season_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE championship_team (id VARCHAR(36) NOT NULL, group_name VARCHAR(100) DEFAULT NULL, championship_id VARCHAR(36) NOT NULL, team_id VARCHAR(36) NOT NULL, INDEX IDX_E0E6935694DDBCE9 (championship_id), INDEX IDX_E0E69356296CD8AE (team_id), UNIQUE INDEX uniq_championship_team (championship_id, team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE season (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE championship_season ADD CONSTRAINT FK_87EC4FC094DDBCE9 FOREIGN KEY (championship_id) REFERENCES championship (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE championship_season ADD CONSTRAINT FK_87EC4FC04EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE championship_team ADD CONSTRAINT FK_E0E6935694DDBCE9 FOREIGN KEY (championship_id) REFERENCES championship (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE championship_team ADD CONSTRAINT FK_E0E69356296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game ADD championship_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE championship_season DROP FOREIGN KEY FK_87EC4FC094DDBCE9');
        $this->addSql('ALTER TABLE championship_season DROP FOREIGN KEY FK_87EC4FC04EC001D1');
        $this->addSql('ALTER TABLE championship_team DROP FOREIGN KEY FK_E0E6935694DDBCE9');
        $this->addSql('ALTER TABLE championship_team DROP FOREIGN KEY FK_E0E69356296CD8AE');
        $this->addSql('DROP TABLE championship');
        $this->addSql('DROP TABLE championship_season');
        $this->addSql('DROP TABLE championship_team');
        $this->addSql('DROP TABLE season');
        $this->addSql('ALTER TABLE game DROP championship_id');
    }
}
