<?php

namespace Semisedlak\Migratte\Migrations;

use InvalidArgumentException;

class Table
{
	private string $name;

	private string $primaryKey;

	private string $fileName;

	private string $groupNo;

	private string $committedAt;

	/**
	 * @param array<string> $options
	 */
	public function __construct(
		array $options
	) {
		if (!isset($options['name'])) {
			throw new InvalidArgumentException('Missing name in table schema');
		}
		$this->name = $options['name'];

		if (!isset($options['primaryKey'])) {
			throw new InvalidArgumentException('Missing primaryKey in table schema');
		}
		$this->primaryKey = $options['primaryKey'];

		if (!isset($options['fileName'])) {
			throw new InvalidArgumentException('Missing fileName in table schema');
		}
		$this->fileName = $options['fileName'];

		if (!isset($options['groupNo'])) {
			throw new InvalidArgumentException('Missing groupNo in table schema');
		}
		$this->groupNo = $options['groupNo'];

		if (!isset($options['committedAt'])) {
			throw new InvalidArgumentException('Missing committedAt in table schema');
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

	public function getGroupNo(): string
	{
		return $this->groupNo;
	}

	public function getCommittedAt(): string
	{
		return $this->committedAt;
	}
}
