<?php

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20260518000300 extends MigrationHelper
{
    public function up(Schema $schema)
    {
        if (!$schema->hasTable('media_folder')) {
            $this->addSql('CREATE TABLE `media_folder` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                UNIQUE INDEX `uniq_media_folder_name` (`name`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }

        if (!$schema->hasTable('media_tag')) {
            $this->addSql('CREATE TABLE `media_tag` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                UNIQUE INDEX `uniq_media_tag_name` (`name`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }

        if (!$schema->hasTable('media')) {
            $this->addSql('CREATE TABLE `media` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `folder_id` INT DEFAULT NULL,
                `uploadedBy_id` INT DEFAULT NULL,
                `filename` VARCHAR(255) NOT NULL,
                `originalName` VARCHAR(255) NOT NULL,
                `path` VARCHAR(255) NOT NULL,
                `thumbnailPath` VARCHAR(255) DEFAULT NULL,
                `webpPath` VARCHAR(255) DEFAULT NULL,
                `mimeType` VARCHAR(120) NOT NULL,
                `size` INT NOT NULL,
                `width` INT DEFAULT NULL,
                `height` INT DEFAULT NULL,
                `alt` VARCHAR(255) DEFAULT NULL,
                `caption` LONGTEXT DEFAULT NULL,
                `credit` VARCHAR(255) DEFAULT NULL,
                `hash` VARCHAR(64) DEFAULT NULL,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                INDEX `idx_media_mime` (`mimeType`),
                INDEX `idx_media_hash` (`hash`),
                INDEX `idx_media_created` (`createdAt`),
                INDEX `IDX_6A2CA10C162CB942` (`folder_id`),
                INDEX `IDX_6A2CA10CE91BE56` (`uploadedBy_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE `media` ADD CONSTRAINT `fk_media_folder` FOREIGN KEY (`folder_id`) REFERENCES `media_folder` (`id`) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE `media` ADD CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploadedBy_id`) REFERENCES `user` (`id`) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('media_media_tag')) {
            $this->addSql('CREATE TABLE `media_media_tag` (
                `media_id` INT NOT NULL,
                `mediatag_id` INT NOT NULL,
                INDEX `IDX_6DB876F0EA9FDD75` (`media_id`),
                INDEX `IDX_6DB876F02FF34B3C` (`mediatag_id`),
                PRIMARY KEY(`media_id`, `mediatag_id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE `media_media_tag` ADD CONSTRAINT `fk_media_media_tag_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE `media_media_tag` ADD CONSTRAINT `fk_media_media_tag_tag` FOREIGN KEY (`mediatag_id`) REFERENCES `media_tag` (`id`) ON DELETE CASCADE');
        }

        if (!$schema->hasTable('news_media')) {
            $this->addSql('CREATE TABLE `news_media` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `news_id` INT NOT NULL,
                `media_id` INT NOT NULL,
                `ordering` INT NOT NULL,
                `altOverride` VARCHAR(255) DEFAULT NULL,
                `captionOverride` LONGTEXT DEFAULT NULL,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                INDEX `IDX_7A3D2E6DB5A459A0` (`news_id`),
                INDEX `IDX_7A3D2E6DEA9FDD75` (`media_id`),
                INDEX `idx_news_media_ordering` (`ordering`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE `news_media` ADD CONSTRAINT `fk_news_media_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE `news_media` ADD CONSTRAINT `fk_news_media_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema)
    {
        $this->dropTableIfExists($schema, 'news_media');
        $this->dropTableIfExists($schema, 'media_media_tag');
        $this->dropTableIfExists($schema, 'media');
        $this->dropTableIfExists($schema, 'media_tag');
        $this->dropTableIfExists($schema, 'media_folder');
    }
}
