<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing indexes on organization_id, team_id, championship_id scoping columns';
    }

    public function up(Schema $schema): void
    {
        // game: scoping columns used in every filtered query
        $this->addSql('CREATE INDEX idx_game_org        ON game (organization_id)');
        $this->addSql('CREATE INDEX idx_game_team       ON game (team_id)');
        $this->addSql('CREATE INDEX idx_game_champ      ON game (championship_id)');
        $this->addSql('CREATE INDEX idx_game_status     ON game (status)');
        $this->addSql('CREATE INDEX idx_game_date       ON game (date)');

        // org-scoped lookup columns added in migration 202823
        $this->addSql('CREATE INDEX idx_player_org      ON player (organization_id)');
        $this->addSql('CREATE INDEX idx_team_org        ON team (organization_id)');
        $this->addSql('CREATE INDEX idx_season_org      ON season (organization_id)');
        $this->addSql('CREATE INDEX idx_championship_org ON championship (organization_id)');
        $this->addSql('CREATE INDEX idx_user_org        ON `user` (organization_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_game_org        ON game');
        $this->addSql('DROP INDEX idx_game_team       ON game');
        $this->addSql('DROP INDEX idx_game_champ      ON game');
        $this->addSql('DROP INDEX idx_game_status     ON game');
        $this->addSql('DROP INDEX idx_game_date       ON game');
        $this->addSql('DROP INDEX idx_player_org      ON player');
        $this->addSql('DROP INDEX idx_team_org        ON team');
        $this->addSql('DROP INDEX idx_season_org      ON season');
        $this->addSql('DROP INDEX idx_championship_org ON championship');
        $this->addSql('DROP INDEX idx_user_org        ON `user`');
    }
}
