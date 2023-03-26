<?php

namespace Semisedlak\Migratte\Migrations;

use Dibi\Connection;
use Dibi\Result;
use Semisedlak\Migratte\Application\DriverFactory;

class Table
{
	private string $name;

	private string $primaryKey;

	private string $fileName;

	private string $committedAt;

	/**
	 * @param array<string> $options
	 */
	public function __construct(
		array $options
	) {
		if (!isset($options['name'])) {
			throw new \InvalidArgumentException('Missing name in table schema');
		}
		$this->name = $options['name'];

		if (!isset($options['primaryKey'])) {
			throw new \InvalidArgumentException('Missing primaryKey in table schema');
		}
		$this->primaryKey = $options['primaryKey'];

		if (!isset($options['fileName'])) {
			throw new \InvalidArgumentException('Missing fileName in table schema');
		}
		$this->fileName = $options['fileName'];

		if (!isset($options['committedAt'])) {
			throw new \InvalidArgumentException('Missing committedAt in table schema');
		}
		$this->committedAt = $options['committedAt'];
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPrimaryKey(): string
	{
		return $this->primaryKey;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getCommittedAt(): string
	{
		return $this->committedAt;
	}

	public function create(Connection $connection): Result
	{
		$driver = DriverFactory::create($connection);
		$sql = $driver->getCreateTableSQL($this);

		return $connection->nativeQuery($sql);
	}

	public function update(Connection $connection): void
	{

	}
}
