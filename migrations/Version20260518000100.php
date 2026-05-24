<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20260518000100 extends MigrationHelper
{
    public function up(Schema $schema): void
    {
        $this->addColumnIfMissing($schema, 'news', 'isIndex', 'TINYINT(1) NOT NULL DEFAULT 1');
        $this->addColumnIfMissing($schema, 'news', 'isFollow', 'TINYINT(1) NOT NULL DEFAULT 1');
        $this->addColumnIfMissing($schema, 'newscategory', 'isIndex', 'TINYINT(1) NOT NULL DEFAULT 1');
        $this->addColumnIfMissing($schema, 'newscategory', 'isFollow', 'TINYINT(1) NOT NULL DEFAULT 1');
        $this->addColumnIfMissing($schema, 'tag', 'isIndex', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->addColumnIfMissing($schema, 'tag', 'isFollow', 'TINYINT(1) NOT NULL DEFAULT 1');

        $this->addSql('ALTER TABLE `news` MODIFY `isIndex` TINYINT(1) NOT NULL DEFAULT 1, MODIFY `isFollow` TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE `newscategory` MODIFY `isIndex` TINYINT(1) NOT NULL DEFAULT 1, MODIFY `isFollow` TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE `tag` MODIFY `isIndex` TINYINT(1) NOT NULL DEFAULT 0, MODIFY `isFollow` TINYINT(1) NOT NULL DEFAULT 1');

        if (!$schema->hasTable('seo_redirect')) {
            $this->addSql('CREATE TABLE `seo_redirect` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `sourcePath` VARCHAR(255) NOT NULL,
                `targetPath` VARCHAR(255) NOT NULL,
                `statusCode` INT NOT NULL DEFAULT 301,
                `enable` TINYINT(1) NOT NULL DEFAULT 1,
                `hits` INT NOT NULL DEFAULT 0,
                `lastAccessedAt` DATETIME DEFAULT NULL,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                UNIQUE INDEX `uniq_seo_redirect_source` (`sourcePath`),
                INDEX `idx_seo_redirect_source` (`sourcePath`),
                INDEX `idx_seo_redirect_enable` (`enable`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->dropTableIfExists($schema, 'seo_redirect');
    }
}
