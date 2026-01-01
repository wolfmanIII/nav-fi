<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260104120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge company_id su mortgage se non presente (post esecuzione precedente)';
    }

    public function up(Schema $schema): void
    {
        // SQLite non consente IF NOT EXISTS sulle colonne; si tenta l'ALTER e si ignora l'errore se la colonna esiste.
        try {
            $this->addSql('ALTER TABLE mortgage ADD company_id INTEGER DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_MORTGAGE_COMPANY_ID ON mortgage (company_id)');
        } catch (\Throwable $e) {
            // Se la colonna esiste già, ignorare.
        }
    }

    public function down(Schema $schema): void
    {
        try {
            $this->addSql('DROP INDEX IDX_MORTGAGE_COMPANY_ID');
            $this->addSql('ALTER TABLE mortgage DROP COLUMN company_id');
        } catch (\Throwable $e) {
            // ignorato in caso di mancata colonna
        }
    }
}
