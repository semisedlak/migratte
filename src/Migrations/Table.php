<?php

namespace Semisedlak\Migratte\Migrations;

/**
 * @property-read string $primaryKey
 * @property-read string $fileName
 * @property-read string $committedAt
 */
class Table
{
	private string $name;

	/** @var array<string, string> */
	private array $fields;

	public function __construct(
		string $name,
		string $primaryKey,
		string $fileName,
		string $commitedAt
	) {
		$this->name = $name;
		$this->fields = [
			'primaryKey'  => $primaryKey,
			'fileName'    => $fileName,
			'committedAt' => $commitedAt,
		];
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function __get(string $name): ?string
	{
		return $this->fields[$name] ?? null;
	}
}
