<?php

namespace Semisedlak\Migratte\Application;

use RuntimeException;

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
		// Support for creating the table if it does not exist
		$this->config->getDriver()->createTable();

		// Support for adding new columns to the table
		$this->config->getDriver()->updateTable();
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
			throw new RuntimeException('Cannot open migrations directory');
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

	public function getMigrationPath(string $migrationFile): string
	{
		$config = $this->getConfig();

		return $config->migrationsDir . '/' . $migrationFile;
	}
}
