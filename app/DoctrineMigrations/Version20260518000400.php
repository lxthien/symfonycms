<?php

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20260518000400 extends MigrationHelper
{
    public function up(Schema $schema)
    {
        if (!$schema->hasTable('audit_log')) {
            $this->addSql('CREATE TABLE `audit_log` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `action` VARCHAR(20) NOT NULL,
                `entityType` VARCHAR(120) NOT NULL,
                `entityId` VARCHAR(64) DEFAULT NULL,
                `entityLabel` VARCHAR(255) DEFAULT NULL,
                `changes` LONGTEXT DEFAULT NULL,
                `user_id` INT DEFAULT NULL,
                `username` VARCHAR(180) DEFAULT NULL,
                `ip` VARCHAR(45) DEFAULT NULL,
                `userAgent` LONGTEXT DEFAULT NULL,
                `route` VARCHAR(120) DEFAULT NULL,
                `requestMethod` VARCHAR(12) DEFAULT NULL,
                `requestUri` LONGTEXT DEFAULT NULL,
                `createdAt` DATETIME NOT NULL,
                INDEX `idx_audit_created` (`createdAt`),
                INDEX `idx_audit_entity` (`entityType`, `entityId`),
                INDEX `idx_audit_action` (`action`),
                INDEX `idx_audit_user` (`user_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

            $this->addSql('ALTER TABLE `audit_log` ADD CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('dmishh_settings')) {
            $this->addSql('CREATE TABLE `dmishh_settings` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `value` LONGTEXT DEFAULT NULL,
                `owner_id` VARCHAR(255) DEFAULT NULL,
                INDEX `name_owner_id_idx` (`name`, `owner_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        }

        $this->addSql('DELETE c FROM `comment` c LEFT JOIN `news` n ON n.id = c.news_id WHERE n.id IS NULL');
        $this->addSql('UPDATE `comment` c LEFT JOIN `comment` parent_comment ON parent_comment.id = c.comment_id SET c.comment_id = NULL WHERE c.comment_id IS NOT NULL AND parent_comment.id IS NULL');

        $this->addIndexIfMissing($schema, 'comment', 'idx_comment_news', '`news_id`');
        $this->addIndexIfMissing($schema, 'comment', 'idx_comment_parent', '`comment_id`');
        $this->addIndexIfMissing($schema, 'comment', 'idx_comment_approved', '`approved`');
        $this->addForeignKeyIfMissing(
            $schema,
            'comment',
            'FK_9474526CB5A459A0',
            'ALTER TABLE `comment` ADD CONSTRAINT `FK_9474526CB5A459A0` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE'
        );
        $this->addForeignKeyIfMissing(
            $schema,
            'comment',
            'FK_9474526CF8697D13',
            'ALTER TABLE `comment` ADD CONSTRAINT `FK_9474526CF8697D13` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`id`) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema)
    {
        $this->dropTableIfExists($schema, 'audit_log');
    }
}
