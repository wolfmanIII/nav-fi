<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260103111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge expirationDay/expirationYear su Income';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE income ADD expiration_day INT DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD expiration_year INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE income DROP expiration_day');
        $this->addSql('ALTER TABLE income DROP expiration_year');
    }
}
