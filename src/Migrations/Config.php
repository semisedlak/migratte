<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeZone;
use Dibi\Connection;

/**
 * @property-read string $migrationsDir
 * @property-read string $migrationsTable
 */
class Config
{
	private Connection $connection;

	private DateTimeZone $timeZone;

	private array $options;

	public function __construct(array $options = [])
	{
		$baseOptions = [
			'timezone'        => 'UTC',
			'migrationsDir'   => __DIR__ . '/../../../database/migrations',
			'migrationsTable' => 'migrations',
			'connection'      => [
				'driver'   => 'sqlite',
				'database' => __DIR__ . '/../../../database/migratte.s3db',
			],
		];

		$this->options = array_merge($baseOptions, $options);

		date_default_timezone_set($this->options['timezone']);

		$this->connection = new Connection($this->options['connection']);
		$this->timeZone = new DateTimeZone(date_default_timezone_get());

		$dir = $this->options['migrationsDir'];
		if (!is_dir($dir)) {
			mkdir($dir, 0777, TRUE);
		}
		$this->options['migrationsDir'] = realpath($dir);
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	public function getTimeZone(): DateTimeZone
	{
		return $this->timeZone;
	}

	public function __get($name)
	{
		return $this->options[$name] ?? NULL;
	}
}