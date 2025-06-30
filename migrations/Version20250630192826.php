<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630192826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            UPDATE "user" SET password = '$2y$13$BKGSpnYp9wC4ecUcEtnLSOmYknmxMxs8xY62RoRR43RHzd4E4U7wC' WHERE password IS NULL
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER password SET NOT NULL
        SQL
        );
        //if password is not set, set it to 'password'
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER password DROP NOT NULL
        SQL);
    }
}
