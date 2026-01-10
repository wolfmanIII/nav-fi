<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aggiunge i campi delivery_proof_* alle tabelle dei dettagli Income.
 */
final class Version20260111120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery proof fields to income detail tables';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'income_charter_details',
            'income_subsidy_details',
            'income_freight_details',
            'income_services_details',
            'income_passengers_details',
            'income_mail_details',
            'income_trade_details',
        ];

        foreach ($tables as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN delivery_proof_ref VARCHAR(255) DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN delivery_proof_day INTEGER DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN delivery_proof_year INTEGER DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN delivery_proof_received_by VARCHAR(255) DEFAULT NULL', $table));
        }
    }

    public function down(Schema $schema): void
    {
        // Revert non supportato su SQLite senza ricreare le tabelle; ripristinare manualmente se necessario.
    }
}
