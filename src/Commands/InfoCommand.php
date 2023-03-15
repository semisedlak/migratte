<?php

namespace Semisedlak\Migratte\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
	protected static $defaultName = 'migratte:info';

	protected function configure()
	{
		$this->setDescription('Show migrations configuration info');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$driver = $config->getConnection()->getDriver();
		$table = $config->getTable();

		$rows = [
			[
				'Timezone:',
				$this->prepareOutput($config->timezone, 'cyan'),
			],
			[
				'Migrations directory:',
				$this->prepareOutput($config->migrationsDir, 'cyan'),
			],
			new TableSeparator(),
			[
				'Table name:',
				$this->prepareOutput($table->getName(), 'cyan'),
			],
			[
				'Primary key:',
				$this->prepareOutput($table->primaryKey, 'cyan'),
			],
			[
				'"File name" field:',
				$this->prepareOutput($table->fileName, 'cyan'),
			],
			[
				'"Committed at" field:',
				$this->prepareOutput($table->committedAt, 'cyan'),
			],
			new TableSeparator(),
			[
				'Driver:',
				$this->prepareOutput($config->connection['driver'], 'cyan'),
			],
			[
				'Driver class:',
				$this->prepareOutput(get_class($driver), 'cyan'),
			],
			[
				'Host:',
				$this->prepareOutput($config->connection['host'] ?? '', 'cyan'),
			],
			[
				'Database:',
				$this->prepareOutput($config->connection['database'] ?? 'N/A', 'cyan'),
			],
			[
				'User:',
				$this->prepareOutput($config->connection['user'] ?? '', 'cyan'),
			],
			[
				'Password:',
				$this->prepareOutput(str_repeat('*', strlen($config->connection['pass'] ?? '')), 'cyan'),
			],
			[
				'Charset:',
				$this->prepareOutput($config->connection['charset'] ?? '', 'cyan'),
			],
		];

		$table = new Table($output);
		$table->setStyle('box-double')
			->setHeaders([
				'Setting',
				'Value',
			])
			->setRows($rows);
		$table->render();
		$this->writeln('');

		$rows = [];
		/** @var Command $commandClass */
		foreach ($this->kernel->getCommandClasses() as $commandClass) {
			$rows[] = [
				$this->prepareOutput($commandClass::getDefaultName(), 'yellow'),
				$commandClass,
			];
		}
		$table = new Table($output);
		$table->setStyle('box-double')
			->setHeaders([
				'Command',
				'Class',
			])
			->setRows($rows);
		$table->render();
		$this->writeln('');

		return 0;
	}
}
