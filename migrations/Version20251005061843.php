<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005061843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE membership_cards (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, cardnumber_c VARCHAR(50) NOT NULL, issuedate DATE NOT NULL, expiry_date DATE DEFAULT NULL, status TINYINT(1) NOT NULL, roleoncard VARCHAR(100) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, INDEX IDX_80F7CF0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(255) DEFAULT NULL, paymentdate DATETIME NOT NULL, status VARCHAR(255) DEFAULT NULL, reference VARCHAR(255) NOT NULL, INDEX IDX_65D29B32A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE membership_cards ADD CONSTRAINT FK_80F7CF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership_cards DROP FOREIGN KEY FK_80F7CF0A76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32A76ED395');
        $this->addSql('DROP TABLE membership_cards');
        $this->addSql('DROP TABLE payments');
    }
}
