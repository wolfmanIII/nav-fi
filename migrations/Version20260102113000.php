<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260102113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge cancel_day e cancel_year a income';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('income');
        $table->addColumn('cancel_day', 'integer', ['notnull' => false]);
        $table->addColumn('cancel_year', 'integer', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('income');
        $table->dropColumn('cancel_day');
        $table->dropColumn('cancel_year');
    }
}
