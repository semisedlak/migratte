<?php

namespace Semisedlak\Migratte\Commands;

use DateTime;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
	protected static $defaultName = 'migratte:status';

	protected function configure()
	{
		$this->setDescription('Show migrations status');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$migrationFiles = $this->kernel->getMigrationFilesList();

		$this->write('Migrations dir: ');
		$this->writelnCyan($config->migrationsDir);

		$migrations = [];
		$pointingOnNext = FALSE;
		$nonCommittedCount = 0;
		$i = 0;
		foreach ($migrationFiles as $migrationFile) {
			$i++;
			$className = $this->kernel->parseMigrationClassName($migrationFile);
			$committedAt = $this->kernel->getCommittedAt($migrationFile);

			require_once $this->kernel->getMigrationPath($migrationFile);

			/** @var Migration $migration */
			$migration = new $className($this->kernel, $migrationFile, $committedAt);

			$createdAt = DateTime::createFromFormat('\M\i\g\r\a\t\i\o\n_Ymd_His', $className);
			$isCommitted = $migration->isCommitted();
			$isBreakpoint = $migration::isBreakpoint();
			$name = $migration::getName();

			if (!$isCommitted) {
				$nonCommittedCount++;
			}

			if (!$isCommitted && !$pointingOnNext) {
				$number = '=> ' . $i;
				$pointingOnNext = TRUE;
			} else {
				$number = '   ' . $i;
			}

			$migrations[] = [
				'no'         => $number,
				'migrated'   => $this->prepareOutput($isCommitted ? 'YES' : 'NO', $isCommitted ? 'green' : 'yellow'),
				'breakpoint' => $isBreakpoint ? $this->prepareOutput(' BP ', 'black', 'yellow') : '',
				'name'       => $this->prepareOutput($name, $isBreakpoint ? 'yellow' : 'white'),
				'committed'  => $isCommitted ? $committedAt->format('Y-m-d H:i:s') : '-',
				'created'    => $createdAt->format('Y-m-d H:i:s'),
				'file'       => $migration->getFileName(),
			];

			if ($isCommitted && $migration::isBreakpoint() && $i < count($migrationFiles)) {
				$migrations[] = new TableSeparator();
			}
		}

		if (count($migrationFiles) > 0) {
			$table = new Table($output);
			$table->setStyle('box-double')
				->setHeaders([
					'No.',
					'Done',
					'BP',
					'Migration name',
					'Committed at',
					'Created at',
					'File name',
				])
				->setRows($migrations);
			$table->render();

			$this->writeln('');
			if ($nonCommittedCount > 0) {
				$this->writelnYellow('Database is ' . $nonCommittedCount . ' migration' . ($nonCommittedCount != 1 ? 's' : '') . ' behind');
			} else {
				$this->writelnGreen('Database is up-to-date');
			}
		} else {
			$this->writeln('');
			$this->writelnWarning(' No migration files ');
		}
		$this->writeln('');

		return 0;
	}
}