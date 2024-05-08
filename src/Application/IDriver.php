<?php

namespace Semisedlak\Migratte\Application;

use DateTimeImmutable;
use DateTimeZone;
use Dibi\Row;
use Semisedlak\Migratte\Migrations\Table;

interface IDriver
{
	public function getTable(): Table;

	public function createTable(): void;

	public function updateTable(): void;

	public function getMigrationByFileName(string $fileName): ?Row;

	public function getMigrationGroupNo(?Row $migrationRow): ?int;

	public function getMigrationCommittedAt(?Row $migrationRow, DateTimeZone $timeZone): ?DateTimeImmutable;

	public function commitMigration(string $fileName, ?int $groupNo = null): void;

	public function rollbackMigration(int $migrationId): void;

	/**
	 * @return Row[]
	 */
	public function getRollbackMigrationsList(string $strategy, ?string $fileName = null, ?int $limit = null): array;

	public function getMaxGroupNo(): ?int;

	public function getNextGroupNo(): int;

	/**
	 * @return array<string>
	 */
	public function getNewColumns(): array;
}
