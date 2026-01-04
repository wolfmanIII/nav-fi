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

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('campaign')) {
            $schema->dropTable('campaign');
        }

        if ($schema->hasTable('ship')) {
            $ship = $schema->getTable('ship');
            if ($ship->hasForeignKey('FK_EF4E8F97F639F774')) {
                $ship->removeForeignKey('FK_EF4E8F97F639F774');
            }
            if ($ship->hasIndex('IDX_EF4E8F97F639F774')) {
                $ship->dropIndex('IDX_EF4E8F97F639F774');
            }
            if ($ship->hasColumn('campaign_id')) {
                $ship->dropColumn('campaign_id');
            }
        }
    }
}
