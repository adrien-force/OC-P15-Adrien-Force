<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250701180421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD roles JSONB
        SQL);
        $this->addSql(
            <<<'SQL'
            ALTER TABLE "user" ALTER COLUMN roles SET DATA TYPE JSONB USING roles::jsonb
        SQL
        );
        $this->addSql(<<<'SQL'
            UPDATE "user" SET roles = '["ROLE_ADMIN"]'::jsonb WHERE admin = true;
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE "user" SET roles = '["ROLE_USER"]'::jsonb WHERE roles IS NULL;
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP admin
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER COLUMN roles SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD admin BOOLEAN
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE "user" SET admin = false;
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER COLUMN admin SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP roles
        SQL);
    }
}
