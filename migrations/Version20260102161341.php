<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Segnaposto per la versione 20260102161341 già eseguita.
 * Non esegue modifiche allo schema: serve a riallineare i metadati delle migrazioni.
 */
final class Version20260102161341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrazione segnaposto (nessuna modifica allo schema)';
    }

    public function up(Schema $schema): void
    {
        // Nessuna operazione: migrazione già eseguita in precedenza.
    }

    public function down(Schema $schema): void
    {
        // Nessuna operazione: migrazione segnaposto.
    }
}
