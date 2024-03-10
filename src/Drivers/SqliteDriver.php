<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Row;
use Semisedlak\Migratte\Application\IDriver;

class SqliteDriver extends AbstractDriver implements IDriver
{
	public function createTable(): void
	{
		$tableName = $this->table->getName();
		$primaryKey = $this->table->getPrimaryKey();
		$fileName = $this->table->getFileName();
		$committedAt = $this->table->getCommittedAt();

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

	public function updateTable(): void
	{
		$tableName = $this->table->getName();
		$newColumns = $this->getNewColumns();

		if (!$newColumns) {
			return;
		}

		$columnsQuery = "PRAGMA table_info('$tableName');";
		/** @var Row[] $existingColumns */
		$existingColumns = $this->connection->nativeQuery($columnsQuery)
			->fetchAll();

		foreach ($existingColumns as &$column) {
			if (isset($column['name'])) {
				$column['column_name'] = $column['name'];
			}
		}

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

	public function getMaxGroupNo(): ?int
	{
		$tableName = $this->table->getName();
		$groupNo = $this->table->getGroupNo();

		$sql = <<<SQL
SELECT MAX(`$groupNo`) AS `max` FROM `$tableName`;
SQL;

		/** @var int|null $max */
		$max = $this->connection->nativeQuery($sql)
			->fetchSingle();

		return $max;
	}

	public function getNextGroupNo(): int
	{
		$groupNo = $this->getMaxGroupNo();
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
