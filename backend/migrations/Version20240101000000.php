<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create findings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE findings (
            id VARCHAR(20) NOT NULL,
            location LONGTEXT NOT NULL,
            risk_range VARCHAR(10) NOT NULL,
            comment LONGTEXT NOT NULL,
            recommendations LONGTEXT NOT NULL,
            resolved TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE findings');
    }
}
