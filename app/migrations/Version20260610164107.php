<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610164107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clicks (id BINARY(16) NOT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, referer LONGTEXT DEFAULT NULL, device VARCHAR(20) DEFAULT NULL, browser VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, url_id BINARY(16) NOT NULL, INDEX IDX_20DA190181CFDAE7 (url_id), INDEX idx_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE urls (id BINARY(16) NOT NULL, original LONGTEXT NOT NULL, short_code VARCHAR(10) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_2A9437A117D2FE0D (short_code), INDEX idx_short_code (short_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE clicks ADD CONSTRAINT FK_20DA190181CFDAE7 FOREIGN KEY (url_id) REFERENCES urls (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clicks DROP FOREIGN KEY FK_20DA190181CFDAE7');
        $this->addSql('DROP TABLE clicks');
        $this->addSql('DROP TABLE urls');
    }
}
