<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20260518000200 extends MigrationHelper
{
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('content_revision')) {
            $this->addSql('CREATE TABLE `content_revision` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `news_id` INT NOT NULL,
                `author_id` INT DEFAULT NULL,
                `revisionType` VARCHAR(32) NOT NULL,
                `title` VARCHAR(255) DEFAULT NULL,
                `url` VARCHAR(255) DEFAULT NULL,
                `description` LONGTEXT DEFAULT NULL,
                `contents` LONGTEXT DEFAULT NULL,
                `pageTitle` VARCHAR(255) DEFAULT NULL,
                `pageDescription` LONGTEXT DEFAULT NULL,
                `pageKeyword` VARCHAR(255) DEFAULT NULL,
                `qa` LONGTEXT DEFAULT NULL,
                `template` VARCHAR(255) DEFAULT NULL,
                `diffSummary` LONGTEXT DEFAULT NULL,
                `createdAt` DATETIME NOT NULL,
                INDEX `idx_content_revision_news_created` (`news_id`, `createdAt`),
                INDEX `idx_content_revision_type` (`revisionType`),
                INDEX `idx_content_revision_author` (`author_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE `content_revision` ADD CONSTRAINT `fk_content_revision_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE `content_revision` ADD CONSTRAINT `fk_content_revision_author` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('news_view_stat')) {
            $this->addSql('CREATE TABLE `news_view_stat` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `news_id` INT NOT NULL,
                `viewDate` DATE NOT NULL,
                `views` INT NOT NULL DEFAULT 0,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                UNIQUE INDEX `uniq_news_view_stat_day` (`news_id`, `viewDate`),
                INDEX `idx_news_view_stat_date` (`viewDate`),
                INDEX `idx_news_view_stat_news` (`news_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE `news_view_stat` ADD CONSTRAINT `fk_news_view_stat_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->dropTableIfExists($schema, 'news_view_stat');
        $this->dropTableIfExists($schema, 'content_revision');
    }
}
