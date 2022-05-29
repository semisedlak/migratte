<?php

namespace Semisedlak\Migratte\Nette;

use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Semisedlak\Migratte\Tracy\Panel;

class DiExtension extends CompilerExtension
{
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
		$builder = $this->getContainerBuilder();

		if ($this->config->debug) {
			if (!$this->config->connection) {
				$extensions = $this->compiler->getExtensions();
				$dibiExtension = $extensions['dibi'] ?? NULL;
				if ($dibiExtension) {
					$this->config->connection = $dibiExtension->getConfig();
				}
			}

			$panelServiceName = $this->prefix('panel');
			$builder->addDefinition($panelServiceName)
				->setFactory(Panel::class, [$this->config]);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		if ($this->config->debug) {
			$initialize = $class->getMethod('initialize');
			$initialize->addBody('$this->getService(?)->addPanel($this->getService(?));', ['tracy.bar', $this->prefix('panel')]);
		}
	}
}