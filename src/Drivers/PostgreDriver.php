<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Row;
use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class PostgreDriver extends AbstractDriver implements IDriver
{
	public function createTable(Table $table): void
	{
		$tableName = $table->getName();
		/** @var string $schema */
		$schema = $this->connection->getConfig('schema', 'public');
		$primaryKey = $table->getPrimaryKey();
		$fileName = $table->getFileName();
		$committedAt = $table->getCommittedAt();

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS $schema.$tableName (
	$primaryKey serial PRIMARY KEY,
    $fileName varchar(255) NOT NULL,
	$committedAt timestamp NULL
);
SQL;

		$this->connection->nativeQuery($sql);
	}

	public function updateTable(Table $table): void
	{
		$tableName = $table->getName();
		/** @var string $schema */
		$schema = $this->connection->getConfig('schema', 'public');
		$newColumns = $this->getNewColumns();

		if (!$newColumns) {
			return;
		}

		$columnsQuery = "SELECT column_name FROM information_schema.columns WHERE table_schema = '$schema' AND table_name = '$tableName';";
		/** @var Row[] $existingColumns */
		$existingColumns = $this->connection->nativeQuery($columnsQuery)->fetchAll();

		$columnsToAdd = $this->getColumnsToAdd(
			$newColumns,
			$existingColumns
		);

		if (!$columnsToAdd) {
			return;
		}

		foreach ($columnsToAdd as $columnName => $columnType) {
			$sql = <<<SQL
ALTER TABLE $schema.$tableName ADD COLUMN $columnName $columnType;
SQL;
			$this->connection->nativeQuery($sql);
		}
	}

	public function getMaxGroupNo(Table $table): ?int
	{
		return 0; // todo implement
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
			'group' => 'int NOT NULL',
		];
	}
}
