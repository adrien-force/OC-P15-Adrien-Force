<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250710204336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE album_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE media_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE album (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE media (id INT NOT NULL, user_id INT DEFAULT NULL, album_id INT DEFAULT NULL, path VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6A2CA10CA76ED395 ON media (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6A2CA10C1137ABCF ON media (album_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSONB NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE album_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE media_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE "user_id_seq" CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media DROP CONSTRAINT FK_6A2CA10C1137ABCF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE album
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE media
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
