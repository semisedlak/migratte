<?php

namespace Semisedlak\Migratte\Drivers;

use DateTime;
use Dibi\Connection;
use Dibi\Row;
use Semisedlak\Migratte\Migrations\Table;

class AbstractDriver
{
	public Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	public function commitMigration(Table $table, string $fileName, ?int $groupNo = null): void
	{
		$this->connection->insert($table->getName(), [
			$table->getFileName()    => $fileName,
			$table->getGroupNo()     => $groupNo,
			$table->getCommittedAt() => new DateTime(),
		])->execute();
	}

	public function rollbackMigration(Table $table, int $migrationId): void
	{
		$this->connection->delete($table->getName())
			->where('%n = %i', $table->getPrimaryKey(), $migrationId)
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
