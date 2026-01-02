<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aggiunge il campo secret_level a income_subsidy_details.
 */
final class Version20260108130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add secret_level to income_subsidy_details';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE income_subsidy_details ADD COLUMN secret_level VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // SQLite non supporta DROP COLUMN; per revert creare manualmente la tabella senza secret_level.
    }
}
