<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260102104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabella income con riferimenti a income_category, ship e user';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('income');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 36]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('signing_day', 'integer');
        $table->addColumn('signing_year', 'integer');
        $table->addColumn('payment_day', 'integer', ['notnull' => false]);
        $table->addColumn('payment_year', 'integer', ['notnull' => false]);
        $table->addColumn('amount', 'decimal', ['precision' => 11, 'scale' => 2]);
        $table->addColumn('note', 'text', ['notnull' => false]);
        $table->addColumn('income_category_id', 'integer');
        $table->addColumn('ship_id', 'integer');
        $table->addColumn('user_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['income_category_id']);
        $table->addIndex(['ship_id']);
        $table->addIndex(['user_id']);

        $table->addForeignKeyConstraint('income_category', ['income_category_id'], ['id']);
        $table->addForeignKeyConstraint('ship', ['ship_id'], ['id']);
        $table->addForeignKeyConstraint('user', ['user_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('income');
    }
}
