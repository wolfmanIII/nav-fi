<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260102111000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabella annual_budget';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('annual_budget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 36]);
        $table->addColumn('start_day', 'integer');
        $table->addColumn('start_year', 'integer');
        $table->addColumn('end_day', 'integer');
        $table->addColumn('end_year', 'integer');
        $table->addColumn('note', 'text', ['notnull' => false]);
        $table->addColumn('ship_id', 'integer');
        $table->addColumn('user_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['ship_id']);
        $table->addIndex(['user_id']);

        $table->addForeignKeyConstraint('ship', ['ship_id'], ['id']);
        $table->addForeignKeyConstraint('user', ['user_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('annual_budget');
    }
}
