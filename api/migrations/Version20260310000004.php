<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix drill JSON columns and session_drill index names to match Doctrine expectations';
    }

    public function up(Schema $schema): void
    {
        // Use native JSON type so Doctrine schema validation passes
        $this->addSql('ALTER TABLE drill CHANGE tags tags JSON NOT NULL, CHANGE links links JSON NOT NULL');

        // Rename session_drill FK indexes to Doctrine auto-generated names
        $this->addSql('ALTER TABLE session_drill RENAME INDEX IDX_sd_session TO IDX_7A910F26613FECDF');
        $this->addSql('ALTER TABLE session_drill RENAME INDEX IDX_sd_drill TO IDX_7A910F26D1BD206D');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE drill CHANGE tags tags LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE links links LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE session_drill RENAME INDEX IDX_7A910F26613FECDF TO IDX_sd_session');
        $this->addSql('ALTER TABLE session_drill RENAME INDEX IDX_7A910F26D1BD206D TO IDX_sd_drill');
    }
}
