<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge local_law_id su mortgage, cost e income';
    }

    public function up(Schema $schema): void
    {
        // SQLite: tentiamo l'ALTER e ignoriamo se la colonna esiste già
        try {
            $this->addSql('ALTER TABLE mortgage ADD local_law_id INTEGER DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_MORTGAGE_LOCAL_LAW_ID ON mortgage (local_law_id)');
        } catch (\Throwable $e) {
            // colonna esistente
        }

        try {
            $this->addSql('ALTER TABLE cost ADD local_law_id INTEGER DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_COST_LOCAL_LAW_ID ON cost (local_law_id)');
        } catch (\Throwable $e) {
            // colonna esistente
        }

        try {
            $this->addSql('ALTER TABLE income ADD local_law_id INTEGER DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_INCOME_LOCAL_LAW_ID ON income (local_law_id)');
        } catch (\Throwable $e) {
            // colonna esistente
        }
    }

    public function down(Schema $schema): void
    {
        try {
            $this->addSql('DROP INDEX IDX_MORTGAGE_LOCAL_LAW_ID');
            $this->addSql('ALTER TABLE mortgage DROP COLUMN local_law_id');
        } catch (\Throwable $e) {
        }

        try {
            $this->addSql('DROP INDEX IDX_COST_LOCAL_LAW_ID');
            $this->addSql('ALTER TABLE cost DROP COLUMN local_law_id');
        } catch (\Throwable $e) {
        }

        try {
            $this->addSql('DROP INDEX IDX_INCOME_LOCAL_LAW_ID');
            $this->addSql('ALTER TABLE income DROP COLUMN local_law_id');
        } catch (\Throwable $e) {
        }
    }
}
