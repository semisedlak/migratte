<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeImmutable;
use Dibi\DateTime;
use Dibi\Drivers\MySqliDriver;
use Dibi\Drivers\Sqlite3Driver;
use Dibi\Drivers\SqliteDriver;
use Dibi\Row;
use Nette\Database\Drivers\MySqlDriver;

class Kernel
{
	private Config $config;

	private array $commandClasses;

	public function __construct(Config $config, array $commandClasses = [])
	{
		$this->config = $config;
		$this->commandClasses = $commandClasses;

		$this->prepare();
	}

	private function prepare(): void
	{
		$connection = $this->config->getConnection();
		$driver = $connection->getDriver();
		$table = $this->config->getTable();

		$tableName = $table->getName();

		$sql = '-- noop';
		if ($driver instanceof SqliteDriver || $driver instanceof Sqlite3Driver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS "$tableName"
(
	"{$table->primaryKey}" INTEGER PRIMARY KEY AUTOINCREMENT, 
	"{$table->fileName}" TEXT, 
	"{$table->committedAt}" DATETIME DEFAULT NULL
)
SQL;
		} elseif ($driver instanceof MySqlDriver || $driver instanceof MySqliDriver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `$tableName` (
	`{$table->primaryKey}` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `{$table->fileName}` int NOT NULL,
	`{$table->committedAt}` datetime NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';
SQL;
		}

		$connection->nativeQuery($sql);
	}

	public function getConfig(): Config
	{
		return $this->config;
	}

	public function getCommandClasses(): array
	{
		return $this->commandClasses;
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
			$committedAtDate = $row[$table->committedAt];
			if ($committedAtDate instanceof DateTime) {
				$dateTime = $committedAtDate;
			} else {
				$dateTime = DateTimeImmutable::createFromFormat('U', $committedAtDate);
			}
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