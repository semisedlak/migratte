<?php

namespace Semisedlak\Migratte\Nette;

use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Semisedlak\Migratte\Tracy\Panel;

class DiExtension extends CompilerExtension
{
	private ?bool $debugMode = null;

	private ?bool $cliMode = null;

	public function __construct(?bool $debugMode = null, ?bool $cliMode = null)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'debug'           => Expect::bool(false),
			'timezone'        => Expect::string(),
			'migrationsDir'   => Expect::string(),
			'migrationsTable' => Expect::structure([
				'name'        => Expect::string('migrations'),
				'primaryKey'  => Expect::string('id'),
				'fileName'    => Expect::string('filename'),
				'committedAt' => Expect::string('committed_at'),
			]),
			'connection'      => Expect::mixed(),
		]);
	}

	public function loadConfiguration(): void
	{
		$container = $this->getContainerBuilder();
		if ($this->debugMode === null) {
			$this->debugMode = $container->parameters['debugMode'];
		}

		if ($this->cliMode === null) {
			$this->cliMode = $container->parameters['consoleMode'];
		}

		if ($this->config->debug) {
			if (!$this->config->connection) {
				$extensions = $this->compiler->getExtensions();
				$dibiExtension = $extensions['dibi'] ?? null;
				if ($dibiExtension) {
					$this->config->connection = $dibiExtension->getConfig();
				}
			}

			$panelServiceName = $this->prefix('panel');
			$container->addDefinition($panelServiceName)
				->setFactory(Panel::class, [$this->config]);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		if ($this->config->debug && $this->debugMode && !$this->cliMode) {
			$initialize = $class->getMethod('initialize');
			$initialize->addBody(
				'$this->getService(?)->addPanel($this->getService(?));',
				['tracy.bar', $this->prefix('panel')]
			);
		}
	}
}
