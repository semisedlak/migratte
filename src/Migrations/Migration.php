<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeImmutable;

abstract class Migration
{
	private Kernel $kernel;

	private ?string $fileName = NULL;

	private ?DateTimeImmutable $committedAt = NULL;

	public function __construct(
		Kernel             $kernel,
		?string            $fileName = NULL,
		?DateTimeImmutable $committedAt = NULL
	)
	{
		$this->kernel = $kernel;
		$this->fileName = $fileName;
		$this->committedAt = $committedAt;
	}

	abstract public static function getName(): string;

	abstract public static function up(): string;

	public static function down(): ?string
	{
		return NULL;
	}

	public static function isBreakpoint()
	{
		return static::down() === NULL;
	}

	public function isCommitted(): bool
	{
		return $this->committedAt ? TRUE : FALSE;
	}

	public function getCommittedAt(): ?DateTimeImmutable
	{
		return $this->committedAt;
	}

	public function getFileName(): ?string
	{
		return $this->fileName;
	}
}