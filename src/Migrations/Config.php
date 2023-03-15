<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeZone;
use Dibi\Connection;
use RuntimeException;

/**
 * @property-read string $migrationsDir
 * @property-read string $timezone
 */
class Config
{
	private Connection $connection;

	private DateTimeZone $timeZone;

	private Table $table;

	private array $options;

	public function __construct(array $options = [])
	{
		$workingDir = getcwd();
		$baseOptions = [
			'timezone'        => 'UTC',
			'migrationsDir'   => "$workingDir/database/migrations",
			'migrationsTable' => [
				'name'        => 'migrations',
				'primaryKey'  => 'id',
				'fileName'    => 'filename',
				'committedAt' => 'committed_at',
			],
			'connection'      => [
				'driver'   => 'sqlite',
				'database' => "$workingDir/database/migratte.s3db",
			],
		];

		$options = array_filter($options);

		$this->options = array_merge($baseOptions, $options);
		$this->options['migrationsTable'] = array_merge(
			$baseOptions['migrationsTable'],
			$options['migrationsTable'] ?? []
		);

		$this->table = new Table(
			$this->options['migrationsTable']['name'],
			$this->options['migrationsTable']['primaryKey'],
			$this->options['migrationsTable']['fileName'],
			$this->options['migrationsTable']['committedAt']
		);

		date_default_timezone_set($this->options['timezone']);

		$dir = $this->options['migrationsDir'];
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
			}
		}
		$this->options['migrationsDir'] = realpath($dir);

		$this->timeZone = new DateTimeZone(date_default_timezone_get());
		$this->connection = new Connection($this->options['connection']);
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	public function getTimeZone(): DateTimeZone
	{
		return $this->timeZone;
	}

	public function getTable(): Table
	{
		return $this->table;
	}

	public function __get($name)
	{
		return $this->options[$name] ?? null;
	}
}
