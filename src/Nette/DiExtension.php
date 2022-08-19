<?php

namespace Semisedlak\Migratte\Nette;

use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Semisedlak\Migratte\Tracy\Panel;

class DiExtension extends CompilerExtension
{
	private ?bool $debugMode = NULL;

	private ?bool $cliMode = NULL;

	public function __construct(?bool $debugMode = NULL, ?bool $cliMode = NULL)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'debug'           => Expect::bool(FALSE),
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
		if ($this->debugMode === NULL) {
			$this->debugMode = $container->parameters['debugMode'];
		}

		if ($this->cliMode === NULL) {
			$this->cliMode = $container->parameters['consoleMode'];
		}

		if ($this->config->debug) {
			if (!$this->config->connection) {
				$extensions = $this->compiler->getExtensions();
				$dibiExtension = $extensions['dibi'] ?? NULL;
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
			$initialize->addBody('$this->getService(?)->addPanel($this->getService(?));', ['tracy.bar', $this->prefix('panel')]);
		}
	}
}