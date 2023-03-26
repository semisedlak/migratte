<?php

namespace Semisedlak\Migratte\Application;

use Dibi\Connection;
use Semisedlak\Migratte\Migrations\Table;

interface IDriver
{
	public function createTable(Connection $connection, Table $table): void;

	public function updateTable(Connection $connection, Table $table): void;
}
