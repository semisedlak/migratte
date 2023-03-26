<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Connection;
use Dibi\Row;
use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class SqliteDriver extends AbstractDriver implements IDriver
{
	public function createTable(Connection $connection, Table $table): void
	{
		$tableName = $table->getName();
		$primaryKey = $table->getPrimaryKey();
		$fileName = $table->getFileName();
		$committedAt = $table->getCommittedAt();

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS "$tableName"
(
	"$primaryKey" INTEGER PRIMARY KEY AUTOINCREMENT,
	"$fileName" TEXT,
	"$committedAt" DATETIME DEFAULT NULL
)
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

		$columnsQuery = "PRAGMA table_info('$tableName');";
		/** @var Row[] $existingColumns */
		$existingColumns = $connection->nativeQuery($columnsQuery)
			->fetchAll();

		$columnsToAdd = $this->getColumnsToAdd(
			$newColumns,
			$existingColumns
		);

		if (!$columnsToAdd) {
			return;
		}

		foreach ($columnsToAdd as $columnName => $columnType) {
			$sql = <<<SQL
ALTER TABLE "$tableName" ADD COLUMN "$columnName" $columnType;
SQL;
			$connection->nativeQuery($sql);
		}
	}

	public function getNewColumns(): array
	{
		return [];
	}
}
