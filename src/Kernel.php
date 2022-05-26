<?php

namespace Semisedlak\Migratte;

use DateTimeImmutable;
use Dibi\Drivers\SqliteDriver;
use Dibi\Row;
use Semisedlak\Migratte\Migrations\Config;

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

		$tableName = $this->config->migrationsTable;

		$sql = '-- noop';
		if ($connection->getDriver() instanceof SqliteDriver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS "$tableName"
("id" INTEGER PRIMARY KEY AUTOINCREMENT, "file" TEXT, "committed_at" DATETIME DEFAULT NULL, "is_breakpoint" BOOL)
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

		$row = $connection->select('*')
			->from('%n', $this->config->migrationsTable)
			->orderBy('[id]', 'DESC')
			->fetch();

		return $row ?: NULL;
	}

	public function getCommittedAt(string $fileName): ?DateTimeImmutable
	{
		$row = $this->getMigration($fileName);
		if ($row) {
			return DateTimeImmutable::createFromFormat('U', $row->committed_at)
				->setTimezone($this->config->getTimeZone());
		}

		return NULL;
	}

	public function getMigration(string $migrationFile): ?Row
	{
		$connection = $this->config->getConnection();

		$row = $connection->select('*')
			->from('%n', $this->config->migrationsTable)
			->where('[file] = %s', $migrationFile)
			->fetch();

		return $row ?: NULL;
	}
}