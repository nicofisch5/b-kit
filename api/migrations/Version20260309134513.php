<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309134513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) DEFAULT NULL, team_id VARCHAR(36) DEFAULT NULL, home_team VARCHAR(100) NOT NULL, opposition_team VARCHAR(100) NOT NULL, date DATETIME NOT NULL, opposition_score SMALLINT UNSIGNED DEFAULT 0 NOT NULL, current_quarter VARCHAR(10) DEFAULT \'Q1\' NOT NULL, overtime_count SMALLINT UNSIGNED DEFAULT 0 NOT NULL, status VARCHAR(20) DEFAULT \'in_progress\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_history (id VARCHAR(36) NOT NULL, assist_player_id VARCHAR(36) DEFAULT NULL, sequence INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, game_id VARCHAR(36) NOT NULL, event_id VARCHAR(36) NOT NULL, player_id VARCHAR(36) NOT NULL, assist_event_id VARCHAR(36) DEFAULT NULL, INDEX IDX_B2780F6471F7E88B (event_id), INDEX IDX_B2780F6499E6F5DF (player_id), INDEX IDX_B2780F642981E5F4 (assist_event_id), INDEX idx_history_game (game_id), UNIQUE INDEX uniq_game_sequence (game_id, sequence), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_player (id VARCHAR(36) NOT NULL, jersey_number SMALLINT UNSIGNED NOT NULL, sort_order SMALLINT UNSIGNED DEFAULT 0 NOT NULL, game_id VARCHAR(36) NOT NULL, player_id VARCHAR(36) NOT NULL, INDEX IDX_E52CD7ADE48FD905 (game_id), INDEX IDX_E52CD7AD99E6F5DF (player_id), UNIQUE INDEX uniq_game_player (game_id, player_id), UNIQUE INDEX uniq_game_jersey (game_id, jersey_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id VARCHAR(36) NOT NULL, team_id VARCHAR(36) DEFAULT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quarter (id VARCHAR(36) NOT NULL, quarter_name VARCHAR(10) NOT NULL, sort_order SMALLINT UNSIGNED DEFAULT 0 NOT NULL, game_id VARCHAR(36) NOT NULL, INDEX IDX_1C81E107E48FD905 (game_id), UNIQUE INDEX uniq_game_quarter (game_id, quarter_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stat_event (id VARCHAR(36) NOT NULL, stat_type VARCHAR(20) NOT NULL, timestamp DATETIME NOT NULL, created_at DATETIME NOT NULL, game_id VARCHAR(36) NOT NULL, player_id VARCHAR(36) NOT NULL, quarter_id VARCHAR(36) NOT NULL, INDEX IDX_5789D7B1E48FD905 (game_id), INDEX IDX_5789D7B199E6F5DF (player_id), INDEX IDX_5789D7B1BED4A2B2 (quarter_id), INDEX idx_stat_game_player (game_id, player_id), INDEX idx_stat_game_quarter (game_id, quarter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_history ADD CONSTRAINT FK_B2780F64E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_history ADD CONSTRAINT FK_B2780F6471F7E88B FOREIGN KEY (event_id) REFERENCES stat_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_history ADD CONSTRAINT FK_B2780F6499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_history ADD CONSTRAINT FK_B2780F642981E5F4 FOREIGN KEY (assist_event_id) REFERENCES stat_event (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7AD99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quarter ADD CONSTRAINT FK_1C81E107E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stat_event ADD CONSTRAINT FK_5789D7B1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stat_event ADD CONSTRAINT FK_5789D7B199E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stat_event ADD CONSTRAINT FK_5789D7B1BED4A2B2 FOREIGN KEY (quarter_id) REFERENCES quarter (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_history DROP FOREIGN KEY FK_B2780F64E48FD905');
        $this->addSql('ALTER TABLE game_history DROP FOREIGN KEY FK_B2780F6471F7E88B');
        $this->addSql('ALTER TABLE game_history DROP FOREIGN KEY FK_B2780F6499E6F5DF');
        $this->addSql('ALTER TABLE game_history DROP FOREIGN KEY FK_B2780F642981E5F4');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADE48FD905');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7AD99E6F5DF');
        $this->addSql('ALTER TABLE quarter DROP FOREIGN KEY FK_1C81E107E48FD905');
        $this->addSql('ALTER TABLE stat_event DROP FOREIGN KEY FK_5789D7B1E48FD905');
        $this->addSql('ALTER TABLE stat_event DROP FOREIGN KEY FK_5789D7B199E6F5DF');
        $this->addSql('ALTER TABLE stat_event DROP FOREIGN KEY FK_5789D7B1BED4A2B2');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_history');
        $this->addSql('DROP TABLE game_player');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE quarter');
        $this->addSql('DROP TABLE stat_event');
    }
}
