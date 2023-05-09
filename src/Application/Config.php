<?php

namespace Semisedlak\Migratte\Application;

use DateTimeZone;
use Dibi\Connection;
use Dibi\Exception;
use RuntimeException;
use Semisedlak\Migratte\Migrations\Table;

/**
 * @property-read string $migrationsDir
 * @property-read string $timezone
 */
class Config
{
	private Connection $connection;

	private IDriver $driver;

	private DateTimeZone $timeZone;

	/** @var array<string|string[]> */
	private array $options;

	/**
	 * @param array<string|string[]> $options
	 * @throws Exception
	 */
	public function __construct(array $options = [])
	{
		$workingDir = getcwd();
		$baseOptions = [
			'timezone'        => date_default_timezone_get(),
			'migrationsDir'   => "$workingDir/database/migrations",
			'migrationsTable' => [
				'name'        => 'migrations',
				'primaryKey'  => 'id',
				'fileName'    => 'filename',
				'groupNo'     => 'group',
				'committedAt' => 'committed_at',
			],
			'connection'      => [
				'driver'   => 'sqlite',
				'database' => "$workingDir/database/migratte.s3db",
			],
		];

		$options = array_filter($options);
		/** @var array<string> $migrationsTable */
		$migrationsTable = $options['migrationsTable'] ?? [];

		$this->options = array_merge($baseOptions, $options);
		$this->options['migrationsTable'] = array_merge(
			$baseOptions['migrationsTable'],
			$migrationsTable
		);

		/** @var string $timezone */
		$timezone = $this->options['timezone'];
		/** @var array<string> $connection */
		$connection = $this->options['connection'];

		/** @var string $dir */
		$dir = $this->options['migrationsDir'];
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
			}
		}
		$realPathOfDir = realpath($dir);
		if ($realPathOfDir === false) {
			throw new RuntimeException(sprintf('Directory "%s" does not exist', $dir));
		}
		$this->options['migrationsDir'] = $realPathOfDir;

		$this->timeZone = new DateTimeZone($timezone);
		$this->connection = new Connection($connection);
		$this->driver = DriverFactory::create(
			$this->connection,
			new Table($this->options['migrationsTable'])
		);
	}

	/**
	 * @return array<string|string[]>
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	public function getDriver(): IDriver
	{
		return $this->driver;
	}

	public function getTimeZone(): DateTimeZone
	{
		return $this->timeZone;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get(string $name)
	{
		return $this->options[$name] ?? null;
	}
}
