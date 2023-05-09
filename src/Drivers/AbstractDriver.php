<?php

namespace Semisedlak\Migratte\Drivers;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Dibi\Connection;
use Dibi\Row;
use Semisedlak\Migratte\Application\Kernel;
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

	public function getMigrationByFileName(string $fileName): ?Row
	{
		$table = $this->getTable();

		/** @var Row|null $row */
		$row = $this->connection->select('*')
			->from('%n', $table->getName())
			->where('%n = %s', $table->getFileName(), $fileName)
			->fetch();

		return $row;
	}

	public function getMigrationGroupNo(?Row $migrationRow): ?int
	{
		if (!$migrationRow) {
			return null;
		}

		/** @var int|null $groupNo */
		$groupNo = $migrationRow[$this->table->getGroupNo()];

		return $groupNo;
	}

	public function getMigrationCommittedAt(?Row $migrationRow, DateTimeZone $timeZone): ?DateTimeImmutable
	{
		if (!$migrationRow) {
			return null;
		}

		/** @var \Dibi\DateTime|string $committedAtDate */
		$committedAtDate = $migrationRow[$this->table->getCommittedAt()];
		if ($committedAtDate instanceof DateTime) {
			$dateTime = DateTimeImmutable::createFromFormat(
				'Y-m-d H:i:s',
				$committedAtDate->setTimezone($timeZone)->format('Y-m-d H:i:s'),
				$timeZone
			);
		} else {
			$dateTime = DateTimeImmutable::createFromFormat('U', $committedAtDate, $timeZone);
		}

		return $dateTime ?: null;
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

	public function getRollbackMigrationsList(string $strategy, ?string $fileName = null, ?int $limit = null): array
	{
		$table = $this->getTable();

		switch ($strategy) {
			case Kernel::ROLLBACK_BY_ORDER:
				$sortField = $table->getFileName();
				break;
			case Kernel::ROLLBACK_BY_DATE:
			default:
				$sortField = $table->getPrimaryKey();
		}

		$query = $this->connection->select('*')
			->from('%n', $table->getName());

		if ($strategy == Kernel::ROLLBACK_BY_DATE) {
			$maxGroupNo = $this->getMaxGroupNo();
			if (is_numeric($maxGroupNo)) {
				$query->where('%n = %i', $table->getGroupNo(), $maxGroupNo);
			} elseif (!$limit) {
				$limit = 1;
			}
		}
		if ($fileName) {
			$query->where('%n = %s', $table->getFileName(), $fileName);
		}

		return $query->orderBy('%n DESC', $sortField)
			->fetchAll(null, $limit);
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
