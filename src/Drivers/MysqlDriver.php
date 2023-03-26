<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Connection;
use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class MysqlDriver implements IDriver
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
}
