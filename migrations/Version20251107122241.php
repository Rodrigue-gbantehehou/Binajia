<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107122241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE don ADD payement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9868C0609 FOREIGN KEY (payement_id) REFERENCES payments (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8F081D9868C0609 ON don (payement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9868C0609');
        $this->addSql('DROP INDEX UNIQ_F8F081D9868C0609 ON don');
        $this->addSql('ALTER TABLE don DROP payement_id');
    }
}
