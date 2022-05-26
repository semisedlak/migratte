<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeImmutable;
use Dibi\Drivers\SqliteDriver;
use Dibi\Row;

class Kernel
{
	private Config $config;

	public function __construct(Config $config)
	{
		$this->config = $config;

		$this->prepare();
	}

	private function prepare(): void
	{
		$connection = $this->config->getConnection();
		$table = $this->config->getTable();

		$tableName = $table->getName();

		$sql = '-- noop';
		if ($connection->getDriver() instanceof SqliteDriver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS "$tableName"
(
    "{$table->primaryKey}" INTEGER PRIMARY KEY AUTOINCREMENT, 
    "{$table->fileName}" TEXT, 
    "{$table->committedAt}" DATETIME DEFAULT NULL
)
SQL;
		}

		$connection->nativeQuery($sql);
	}

	public function getConfig(): Config
	{
		return $this->config;
	}

	public function getMigrationFilesList(): array
	{
		$migrationFiles = [];
		$handle = opendir($this->config->migrationsDir);
		while (FALSE !== ($entry = readdir($handle))) {
			if (!is_dir($this->config->migrationsDir . '/' . $entry)) {
				$fileNameParts = explode('.', $entry);
				$extension = strtolower(end($fileNameParts));
				if ($extension === 'php') {
					$migrationFiles[] = $entry;
				}
			}
		}
		closedir($handle);

		sort($migrationFiles);

		return $migrationFiles;
	}

	public function parseMigrationClassName(string $fileName): string
	{
		$datetime = $this->parseMigrationTimestamp($fileName);
		$className = 'Migration_' . $datetime;

		return $className;
	}

	public function parseMigrationTimestamp(string $fileName): string
	{
		return substr($fileName, 0, 15);
	}

	public function getLastMigration(): ?Row
	{
		$connection = $this->config->getConnection();
		$table = $this->config->getTable();

		$row = $connection->select('*')
			->from('%n', $table->getName())
			->orderBy('%n DESC', $table->primaryKey)
			->fetch();

		return $row ?: NULL;
	}

	public function getCommittedAt(string $fileName): ?DateTimeImmutable
	{
		$table = $this->config->getTable();
		$row = $this->getMigration($fileName);
		if ($row) {
			$dateTime = DateTimeImmutable::createFromFormat('U', $row[$table->committedAt]);
			if ($dateTime) {
				$dateTime->setTimezone($this->config->getTimeZone());
			}

			return $dateTime ?: NULL;
		}

		return NULL;
	}

	public function getMigration(string $migrationFile): ?Row
	{
		$connection = $this->config->getConnection();
		$table = $this->config->getTable();

		$row = $connection->select('*')
			->from('%n', $table->getName())
			->where('%n = %s', $table->fileName, $migrationFile)
			->fetch();

		return $row ?: NULL;
	}
}