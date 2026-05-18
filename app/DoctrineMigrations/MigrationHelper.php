<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

abstract class MigrationHelper extends AbstractMigration
{
    protected function addColumnIfMissing(Schema $schema, $tableName, $columnName, $definition)
    {
        if (!$schema->hasTable($tableName) || !$schema->getTable($tableName)->hasColumn($columnName)) {
            $this->addSql(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $tableName, $columnName, $definition));
        }
    }

    protected function addIndexIfMissing(Schema $schema, $tableName, $indexName, $columns)
    {
        if (!$schema->hasTable($tableName) || !$schema->getTable($tableName)->hasIndex($indexName)) {
            $this->addSql(sprintf('ALTER TABLE `%s` ADD INDEX `%s` (%s)', $tableName, $indexName, $columns));
        }
    }

    protected function addUniqueIndexIfMissing(Schema $schema, $tableName, $indexName, $columns)
    {
        if (!$schema->hasTable($tableName) || !$schema->getTable($tableName)->hasIndex($indexName)) {
            $this->addSql(sprintf('ALTER TABLE `%s` ADD UNIQUE INDEX `%s` (%s)', $tableName, $indexName, $columns));
        }
    }

    protected function addForeignKeyIfMissing(Schema $schema, $tableName, $foreignKeyName, $sql)
    {
        if (!$schema->hasTable($tableName) || !$schema->getTable($tableName)->hasForeignKey($foreignKeyName)) {
            $this->addSql($sql);
        }
    }

    protected function dropTableIfExists(Schema $schema, $tableName)
    {
        if ($schema->hasTable($tableName)) {
            $this->addSql(sprintf('DROP TABLE `%s`', $tableName));
        }
    }
}
