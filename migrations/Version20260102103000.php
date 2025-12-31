<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260102103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge il campo note alla tabella cost';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('cost');
        $table->addColumn('note', 'text', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('cost');
        $table->dropColumn('note');
    }
}
