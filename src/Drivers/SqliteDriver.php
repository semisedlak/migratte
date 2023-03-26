<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Connection;
use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class SqliteDriver implements IDriver
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
	}
}
