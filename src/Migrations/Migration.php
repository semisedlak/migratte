<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeImmutable;

abstract class Migration
{
	private string $fileName;

	private ?int $groupNo;

	private ?DateTimeImmutable $committedAt;

	public function __construct(
		string $fileName,
		?int $groupNo,
		?DateTimeImmutable $committedAt = null
	) {
		$this->fileName = $fileName;
		$this->groupNo = $groupNo;
		$this->committedAt = $committedAt;
	}

	abstract public static function getName(): string;

	abstract public static function up(): string;

	public static function down(): ?string
	{
		return null;
	}

	public static function isBreakpoint(): bool
	{
		return static::down() === null;
	}

	public function getGroupNo(): ?int
	{
		return $this->groupNo;
	}

	public function isCommitted(): bool
	{
		return (bool) $this->committedAt;
	}

	public function getCommittedAt(): ?DateTimeImmutable
	{
		return $this->committedAt;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}
}
