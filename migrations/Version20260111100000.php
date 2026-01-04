<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260111100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge tabella campaign e relazione con ship';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform === 'sqlite') {
            $this->addSql('PRAGMA foreign_keys = OFF');
            $this->addSql('CREATE TABLE campaign (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, starting_year INTEGER DEFAULT NULL, session_day INTEGER DEFAULT NULL, session_year INTEGER DEFAULT NULL)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F639F77477153098 ON campaign (code)');
            $this->addSql('ALTER TABLE ship ADD COLUMN campaign_id INTEGER DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_EF4E8F97F639F774 ON ship (campaign_id)');
            $this->addSql('PRAGMA foreign_keys = ON');
        } else {
            // Tabella campaign
            $campaign = $schema->createTable('campaign');
            $campaign->addColumn('id', 'integer', ['autoincrement' => true]);
            $campaign->addColumn('code', 'guid');
            $campaign->addColumn('title', 'string', ['length' => 255]);
            $campaign->addColumn('description', 'text', ['notnull' => false]);
            $campaign->addColumn('starting_year', 'integer', ['notnull' => false]);
            $campaign->addColumn('session_day', 'integer', ['notnull' => false]);
            $campaign->addColumn('session_year', 'integer', ['notnull' => false]);
            $campaign->setPrimaryKey(['id']);
            $campaign->addUniqueIndex(['code'], 'UNIQ_F639F77477153098');

            // Relazione su ship
            $ship = $schema->getTable('ship');
            if (!$ship->hasColumn('campaign_id')) {
                $ship->addColumn('campaign_id', 'integer', ['notnull' => false]);
                $ship->addIndex(['campaign_id'], 'IDX_EF4E8F97F639F774');
                $ship->addForeignKeyConstraint('campaign', ['campaign_id'], ['id'], [], 'FK_EF4E8F97F639F774');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($schema->hasTable('campaign')) {
            $schema->dropTable('campaign');
        }

        if ($schema->hasTable('ship')) {
            $ship = $schema->getTable('ship');
            if ($ship->hasIndex('IDX_EF4E8F97F639F774')) {
                $ship->dropIndex('IDX_EF4E8F97F639F774');
            }
            if ($ship->hasColumn('campaign_id')) {
                $ship->dropColumn('campaign_id');
            }
        }
    }
}
