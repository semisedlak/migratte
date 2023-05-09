<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Row;
use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class SqliteDriver extends AbstractDriver implements IDriver
{
	public function createTable(Table $table): void
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

		$this->connection->nativeQuery($sql);
	}

	public function updateTable(Table $table): void
	{
		$tableName = $table->getName();
		$newColumns = $this->getNewColumns();

		if (!$newColumns) {
			return;
		}

		$columnsQuery = "PRAGMA table_info('$tableName');";
		/** @var Row[] $existingColumns */
		$existingColumns = $this->connection->nativeQuery($columnsQuery)
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
			$this->connection->nativeQuery($sql);
		}
	}

	public function getMaxGroupNo(Table $table): ?int
	{
		$tableName = $table->getName();
		$groupNo = $table->getGroupNo();

		$sql = <<<SQL
SELECT MAX(`$groupNo`) AS `max` FROM `$tableName`;
SQL;

		/** @var int|null $max */
		$max = $this->connection->nativeQuery($sql)
			->fetchSingle();

		return $max;
	}

	public function getNextGroupNo(Table $table): int
	{
		$groupNo = $this->getMaxGroupNo($table);
		if (!$groupNo) {
			return 1;
		}

		return $groupNo + 1;
	}

	public function getNewColumns(): array
	{
		return [
			'group' => 'INT NULL',
		];
	}
}
