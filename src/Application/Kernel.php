<?php

namespace Semisedlak\Migratte\Application;

use DateTimeImmutable;
use Dibi\DateTime;
use Dibi\Drivers\MySqliDriver;
use Dibi\Drivers\PostgreDriver;
use Dibi\Drivers\SqliteDriver;
use Dibi\Row;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Drivers\PgSqlDriver;

class Kernel
{
	private Config $config;

	/** @var array<class-string> */
	private array $commandClasses;

	public const ROLLBACK_BY_ORDER = 'order';
	public const ROLLBACK_BY_DATE = 'date';
	public const ROLLBACK_BY_FILE = 'file';

	/**
	 * @param Config              $config
	 * @param array<class-string> $commandClasses
	 */
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
		$primaryKey = $table->getPrimaryKey();
		$fileName = $table->getFileName();
		$committedAt = $table->getCommittedAt();

		$sql = '-- noop';
		if ($driver instanceof SqliteDriver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS "$tableName"
(
	"$primaryKey" INTEGER PRIMARY KEY AUTOINCREMENT,
	"$fileName" TEXT,
	"$committedAt" DATETIME DEFAULT NULL
)
SQL;
		} elseif ($driver instanceof MySqlDriver || $driver instanceof MySqliDriver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `$tableName` (
	`$primaryKey` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `$fileName` varchar(255) NOT NULL,
	`$committedAt` datetime NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';
SQL;
		} elseif ($driver instanceof PgSqlDriver || $driver instanceof PostgreDriver) {
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS $tableName (
	$primaryKey serial PRIMARY KEY,
    $fileName varchar(255) NOT NULL,
	$committedAt timestamp NULL
);
SQL;
		}

		$connection->nativeQuery($sql);
	}

	public function getConfig(): Config
	{
		return $this->config;
	}

	/**
	 * @return array<class-string>
	 */
	public function getCommandClasses(): array
	{
		return $this->commandClasses;
	}

	/**
	 * @return array<string>
	 */
	public function getMigrationFilesList(): array
	{
		$migrationFiles = [];
		$handle = opendir($this->config->migrationsDir);
		if ($handle === false) {
			throw new \RuntimeException('Cannot open migrations directory');
		}
		while (false !== ($entry = readdir($handle))) {
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

		return 'Migration_' . $datetime;
	}

	public function parseMigrationTimestamp(string $fileName): string
	{
		return substr($fileName, 0, 15);
	}

	/**
	 * @param string      $strategy
	 * @param string|null $fileName
	 * @return array<Row>
	 */
	public function getAllMigrations(
		string $strategy = self::ROLLBACK_BY_DATE,
		string $fileName = null
	): array {
		$connection = $this->config->getConnection();
		$table = $this->config->getTable();

		switch ($strategy) {
			case self::ROLLBACK_BY_ORDER:
				$field = $table->getFileName();
				break;
			case self::ROLLBACK_BY_DATE:
			default:
				$field = $table->getPrimaryKey();
		}

		$rowsQuery = $connection->select('*')
			->from('%n', $table->getName());
		if ($fileName) {
			$rowsQuery->where('%n = %s', $table->getFileName(), $fileName);
		}

		return $rowsQuery->orderBy('%n DESC', $field)
			->fetchAll();
	}

	public function getCommittedAt(string $fileName): ?DateTimeImmutable
	{
		$table = $this->config->getTable();
		$timezone = $this->config->getTimeZone();
		$row = $this->getMigration($fileName);
		if ($row) {
			/** @var DateTime|string $committedAtDate */
			$committedAtDate = $row[$table->getCommittedAt()];
			if ($committedAtDate instanceof DateTime) {
				$dateTime = DateTimeImmutable::createFromFormat(
					'Y-m-d H:i:s',
					$committedAtDate->setTimezone($timezone)->format('Y-m-d H:i:s'),
					$timezone
				);
			} else {
				$dateTime = DateTimeImmutable::createFromFormat('U', $committedAtDate, $timezone);
			}

			return $dateTime ?: null;
		}

		return null;
	}

	public function getMigration(string $migrationFile): ?Row
	{
		$connection = $this->config->getConnection();
		$table = $this->config->getTable();

		/** @var Row|null $row */
		$row = $connection->select('*')
			->from('%n', $table->getName())
			->where('%n = %s', $table->getFileName(), $migrationFile)
			->fetch();

		return $row;
	}

	public function getMigrationPath(string $migrationFile): string
	{
		$config = $this->getConfig();

		return $config->migrationsDir . '/' . $migrationFile;
	}
}
