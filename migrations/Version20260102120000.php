<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260102120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rende la relazione Ship-Mortgage uno-a-uno aggiungendo vincolo univoco su ship_id in mortgage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MORTGAGE_SHIP_ID ON mortgage (ship_id)');
        $this->addSql('ALTER TABLE mortgage ADD company_id INTEGER DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_A64C7D97979B1AD6 ON mortgage (company_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_MORTGAGE_SHIP_ID');
        $this->addSql('DROP INDEX IDX_A64C7D97979B1AD6');
        $this->addSql('ALTER TABLE mortgage DROP COLUMN company_id');
    }
}
