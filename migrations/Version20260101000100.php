<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge le FK opzionali verso user per crew, ship, mortgage e mortgage_installment';
    }

    public function up(Schema $schema): void
    {
        // crew -> user
        $this->addSql('ALTER TABLE crew ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE crew ADD CONSTRAINT FK_F54BC005A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F54BC005A76ED395 ON crew (user_id)');

        // ship -> user
        $this->addSql('ALTER TABLE ship ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ship ADD CONSTRAINT FK_C4F84728A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C4F84728A76ED395 ON ship (user_id)');

        // mortgage -> user
        $this->addSql('ALTER TABLE mortgage ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_D6981E45A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D6981E45A76ED395 ON mortgage (user_id)');

        // mortgage_installment -> user
        $this->addSql('ALTER TABLE mortgage_installment ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mortgage_installment ADD CONSTRAINT FK_F8ACD698A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F8ACD698A76ED395 ON mortgage_installment (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crew DROP FOREIGN KEY FK_F54BC005A76ED395');
        $this->addSql('DROP INDEX IDX_F54BC005A76ED395 ON crew');
        $this->addSql('ALTER TABLE crew DROP user_id');

        $this->addSql('ALTER TABLE ship DROP FOREIGN KEY FK_C4F84728A76ED395');
        $this->addSql('DROP INDEX IDX_C4F84728A76ED395 ON ship');
        $this->addSql('ALTER TABLE ship DROP user_id');

        $this->addSql('ALTER TABLE mortgage DROP FOREIGN KEY FK_D6981E45A76ED395');
        $this->addSql('DROP INDEX IDX_D6981E45A76ED395 ON mortgage');
        $this->addSql('ALTER TABLE mortgage DROP user_id');

        $this->addSql('ALTER TABLE mortgage_installment DROP FOREIGN KEY FK_F8ACD698A76ED395');
        $this->addSql('DROP INDEX IDX_F8ACD698A76ED395 ON mortgage_installment');
        $this->addSql('ALTER TABLE mortgage_installment DROP user_id');
    }
}
