<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Row;
use Semisedlak\Migratte\Application\IDriver;

class MysqlDriver extends AbstractDriver implements IDriver
{
	public function createTable(): void
	{
		$tableName = $this->table->getName();
		$primaryKey = $this->table->getPrimaryKey();
		$fileName = $this->table->getFileName();
		$committedAt = $this->table->getCommittedAt();

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `$tableName` (
	`$primaryKey` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `$fileName` varchar(255) NOT NULL,
	`$committedAt` datetime NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';
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

		$databaseName = $this->connection->getDatabaseInfo()->name;
		$columnsQuery = "SHOW COLUMNS FROM `$tableName` IN `$databaseName`;";
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
ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnType;
SQL;
			$this->connection->nativeQuery($sql);
		}
	}

	public function getMaxGroupNo(): ?int
	{
		return 0; // todo implement
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
			'group' => 'int NOT NULL',
		];
	}
}
