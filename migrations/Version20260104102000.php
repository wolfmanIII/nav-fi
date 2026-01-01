<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260104102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge company e FK company_id su income e cost';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, company_role_id INTEGER NOT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, contact VARCHAR(255) DEFAULT NULL, sign_label VARCHAR(255) DEFAULT NULL, notes CLOB DEFAULT NULL, CONSTRAINT FK_4FBF0947A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4FBF0947FA9442D FOREIGN KEY (company_role_id) REFERENCES company_role (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4FBF0947A76ED395 ON company (user_id)');
        $this->addSql('CREATE INDEX IDX_4FBF0947FA9442D ON company (company_role_id)');
        $this->addSql('ALTER TABLE income ADD company_id INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_249AA25C979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_249AA25C979B1AD6 ON income (company_id)');
        $this->addSql('ALTER TABLE cost ADD company_id INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_3C2AA97979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3C2AA97979B1AD6 ON cost (company_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP INDEX IDX_249AA25C979B1AD6');
        $this->addSql('DROP INDEX IDX_3C2AA97979B1AD6');
        $this->addSql('ALTER TABLE income DROP COLUMN company_id');
        $this->addSql('ALTER TABLE cost DROP COLUMN company_id');
    }
}
