<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge la tabella cost con riferimenti a cost_category, ship e user';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('cost');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 36]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('amount', 'decimal', ['precision' => 11, 'scale' => 2]);
        $table->addColumn('payment_day', 'integer', ['notnull' => false]);
        $table->addColumn('payment_year', 'integer', ['notnull' => false]);
        $table->addColumn('cost_category_id', 'integer', ['notnull' => true]);
        $table->addColumn('ship_id', 'integer', ['notnull' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['cost_category_id']);
        $table->addIndex(['ship_id']);
        $table->addIndex(['user_id']);

        $table->addForeignKeyConstraint('cost_category', ['cost_category_id'], ['id']);
        $table->addForeignKeyConstraint('ship', ['ship_id'], ['id']);
        $table->addForeignKeyConstraint('user', ['user_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('cost');
    }
}
