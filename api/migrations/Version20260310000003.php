<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create drill, training_session, and session_drill tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE drill (
            id VARCHAR(36) NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(150) NOT NULL,
            setup LONGTEXT DEFAULT NULL,
            execution LONGTEXT DEFAULT NULL,
            rotation LONGTEXT DEFAULT NULL,
            evolution LONGTEXT DEFAULT NULL,
            duration SMALLINT UNSIGNED DEFAULT NULL,
            equipment LONGTEXT DEFAULT NULL,
            minimum_players SMALLINT UNSIGNED DEFAULT NULL,
            tags LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
            links LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
            visibility VARCHAR(10) NOT NULL,
            organization_id VARCHAR(36) DEFAULT NULL,
            created_by VARCHAR(36) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_drill_org (organization_id),
            INDEX IDX_drill_created_by (created_by),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE training_session (
            id VARCHAR(36) NOT NULL,
            date DATE NOT NULL,
            goal LONGTEXT DEFAULT NULL,
            duration SMALLINT UNSIGNED DEFAULT NULL,
            comments LONGTEXT DEFAULT NULL,
            organization_id VARCHAR(36) DEFAULT NULL,
            created_by VARCHAR(36) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_ts_org (organization_id),
            INDEX IDX_ts_created_by (created_by),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE session_drill (
            id VARCHAR(36) NOT NULL,
            session_id VARCHAR(36) NOT NULL,
            drill_id VARCHAR(36) NOT NULL,
            sort_order INT NOT NULL,
            note LONGTEXT DEFAULT NULL,
            INDEX IDX_sd_session (session_id),
            INDEX IDX_sd_drill (drill_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE session_drill ADD CONSTRAINT FK_sd_session FOREIGN KEY (session_id) REFERENCES training_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_drill ADD CONSTRAINT FK_sd_drill FOREIGN KEY (drill_id) REFERENCES drill (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE session_drill DROP FOREIGN KEY FK_sd_session');
        $this->addSql('ALTER TABLE session_drill DROP FOREIGN KEY FK_sd_drill');
        $this->addSql('DROP TABLE session_drill');
        $this->addSql('DROP TABLE training_session');
        $this->addSql('DROP TABLE drill');
    }
}
