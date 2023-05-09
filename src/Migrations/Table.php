<?php

namespace Semisedlak\Migratte\Migrations;

use Semisedlak\Migratte\Application\IDriver;

class Table
{
	private IDriver $driver;

	private string $name;

	private string $primaryKey;

	private string $fileName;

	private string $groupNo;

	private string $committedAt;

	/**
	 * @param array<string> $options
	 */
	public function __construct(
		IDriver $driver,
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

		if (!isset($options['groupNo'])) {
			throw new \InvalidArgumentException('Missing groupNo in table schema');
		}
		$this->groupNo = $options['groupNo'];

		if (!isset($options['committedAt'])) {
			throw new \InvalidArgumentException('Missing committedAt in table schema');
		}
		$this->committedAt = $options['committedAt'];
		$this->driver = $driver;
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

	public function getGroupNo(): string
	{
		return $this->groupNo;
	}

	public function getCommittedAt(): string
	{
		return $this->committedAt;
	}

	public function create(): void
	{
		$this->driver->createTable($this);
	}

	public function update(): void
	{
		$this->driver->updateTable($this);
	}
}
