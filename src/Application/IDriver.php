<?php

namespace Semisedlak\Migratte\Application;

use Semisedlak\Migratte\Migrations\Table;

interface IDriver
{
	public function createTable(Table $table): void;

	public function updateTable(Table $table): void;

	public function commitMigration(Table $table, string $fileName, ?int $groupNo = null): void;

	public function rollbackMigration(Table $table, int $migrationId): void;

	public function getMaxGroupNo(Table $table): ?int;

	public function getNextGroupNo(Table $table): int;

	/**
	 * @return array<string>
	 */
	public function getNewColumns(): array;
}
