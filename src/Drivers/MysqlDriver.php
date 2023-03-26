<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Connection;
use Dibi\Row;
use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class MysqlDriver extends AbstractDriver implements IDriver
{
	public function createTable(Connection $connection, Table $table): void
	{
		$tableName = $table->getName();
		$primaryKey = $table->getPrimaryKey();
		$fileName = $table->getFileName();
		$committedAt = $table->getCommittedAt();

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `$tableName` (
	`$primaryKey` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `$fileName` varchar(255) NOT NULL,
	`$committedAt` datetime NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';
SQL;

		$connection->nativeQuery($sql);
	}

	public function updateTable(Connection $connection, Table $table): void
	{
		$tableName = $table->getName();
		$newColumns = $this->getNewColumns();

		if (!$newColumns) {
			return;
		}

		$databaseName = $connection->getDatabaseInfo()->name;
		$columnsQuery = "SHOW COLUMNS FROM `$tableName` IN `$databaseName`;";
		/** @var Row[] $existingColumns */
		$existingColumns = $connection->nativeQuery($columnsQuery)->fetchAll();

		$columnsToAdd = $this->getColumnsToAdd(
			$newColumns,
			$existingColumns
		);

		if (!$columnsToAdd) {
			return;
		}

		foreach ($columnsToAdd as $columnName => $columnType) {
			$sql = <<<SQL
ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnType;
SQL;
			$connection->nativeQuery($sql);
		}
	}

	public function getNewColumns(): array
	{
		return [];
	}
}
