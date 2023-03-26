<?php

namespace Semisedlak\Migratte\Drivers;

use Semisedlak\Migratte\Application\IDriver;
use Semisedlak\Migratte\Migrations\Table;

class PostgreDriver implements IDriver
{
	public function getCreateTableSQL(Table $table): string
	{
		$tableName = $table->getName();
		$primaryKey = $table->getPrimaryKey();
		$fileName = $table->getFileName();
		$committedAt = $table->getCommittedAt();

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS $tableName (
	$primaryKey serial PRIMARY KEY,
    $fileName varchar(255) NOT NULL,
	$committedAt timestamp NULL
);
SQL;

		return $sql;
	}
}
