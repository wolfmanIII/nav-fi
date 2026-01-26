<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126184212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annual_budget ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE asset_amendment ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE company ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE cost ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE crew ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE income ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE mortgage ALTER code TYPE UUID USING code::uuid');
        $this->addSql('ALTER TABLE mortgage_installment ALTER code TYPE UUID USING code::uuid');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE mortgage_installment ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE cost ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE crew ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE income ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE mortgage ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE annual_budget ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE company ALTER code TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE asset_amendment ALTER code TYPE VARCHAR(36)');
    }
}
