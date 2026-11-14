<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20261113221500 extends MigrationHelper
{
    public function up(Schema $schema): void
    {
        $this->addColumnIfMissing($schema, 'news', 'parent_id', 'INT DEFAULT NULL');
        $this->addIndexIfMissing($schema, 'news', 'IDX_NEWS_PARENT', 'parent_id');
        $this->addForeignKeyIfMissing(
            $schema,
            'news',
            'FK_NEWS_PARENT',
            'ALTER TABLE `news` ADD CONSTRAINT `FK_NEWS_PARENT` FOREIGN KEY (`parent_id`) REFERENCES `news` (`id`) ON DELETE SET NULL'
        );
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('news')) {
            $table = $schema->getTable('news');
            if ($table->hasForeignKey('FK_NEWS_PARENT')) {
                $this->addSql('ALTER TABLE `news` DROP FOREIGN KEY `FK_NEWS_PARENT`');
            }
            if ($table->hasIndex('IDX_NEWS_PARENT')) {
                $this->addSql('ALTER TABLE `news` DROP INDEX `IDX_NEWS_PARENT`');
            }
            if ($table->hasColumn('parent_id')) {
                $this->addSql('ALTER TABLE `news` DROP COLUMN `parent_id`');
            }
        }
    }
}

