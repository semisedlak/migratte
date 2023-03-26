<?php

namespace Semisedlak\Migratte\Application;

use Semisedlak\Migratte\Migrations\Table;

interface IDriver
{
	public function getCreateTableSQL(Table $table): string;
}
