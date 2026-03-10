<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cycle table and add cycle_id FK to training_session';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cycle (
            id VARCHAR(36) NOT NULL,
            name VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            outcome LONGTEXT DEFAULT NULL,
            organization_id VARCHAR(36) DEFAULT NULL,
            created_by VARCHAR(36) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_cycle_created_by (created_by),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE training_session ADD COLUMN cycle_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE training_session ADD CONSTRAINT FK_ts_cycle FOREIGN KEY (cycle_id) REFERENCES cycle (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE training_session ADD INDEX IDX_ts_cycle_id (cycle_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training_session DROP FOREIGN KEY FK_ts_cycle');
        $this->addSql('ALTER TABLE training_session DROP INDEX IDX_ts_cycle_id');
        $this->addSql('ALTER TABLE training_session DROP COLUMN cycle_id');
        $this->addSql('DROP TABLE cycle');
    }
}
