<?php

namespace Semisedlak\Migratte\Migrations;

use DateTimeImmutable;
use Semisedlak\Migratte\Kernel;

abstract class Migration
{
	private Kernel $kernel;

	public function __construct(Kernel $kernel)
	{
		$this->kernel = $kernel;
	}

	abstract public static function getName(): string;

	abstract public static function getCreated(): DateTimeImmutable;

	abstract public static function up(): string;

	public static function isBreakpoint()
	{
		return static::down() === NULL;
	}

	public static function down(): ?string
	{
		return NULL;
	}
}