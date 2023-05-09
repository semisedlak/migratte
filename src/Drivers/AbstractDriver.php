<?php

namespace Semisedlak\Migratte\Drivers;

use DateTime;
use Dibi\Connection;
use Dibi\Row;
use Semisedlak\Migratte\Migrations\Table;

class AbstractDriver
{
	protected Connection $connection;

	protected Table $table;

	public function __construct(Connection $connection, Table $table)
	{
		$this->connection = $connection;
		$this->table = $table;
	}

	public function getTable(): Table
	{
		return $this->table;
	}

	public function commitMigration(string $fileName, ?int $groupNo = null): void
	{
		$this->connection->insert($this->table->getName(), [
			$this->table->getFileName()    => $fileName,
			$this->table->getGroupNo()     => $groupNo,
			$this->table->getCommittedAt() => new DateTime(),
		])->execute();
	}

	public function rollbackMigration(int $migrationId): void
	{
		$this->connection->delete($this->table->getName())
			->where('%n = %i', $this->table->getPrimaryKey(), $migrationId)
			->execute();
	}

	/**
	 * @param string[] $newColumns
	 * @param Row[]    $columnsResult
	 * @return string[]
	 */
	protected function getColumnsToAdd(array $newColumns = [], array $columnsResult = []): array
	{
		if (empty($newColumns) || empty($columnsResult)) {
			return [];
		}

		$existingColumns = [];
		foreach ($columnsResult as $column) {
			$existingColumns[] = $column['name'];
		}

		$columnsToAdd = [];
		foreach ($newColumns as $columnName => $columnType) {
			if (!in_array($columnName, $existingColumns)) {
				$columnsToAdd[$columnName] = $columnType;
			}
		}

		return $columnsToAdd;
	}
}
