<?php

namespace Semisedlak\Migratte\Commands;

use DateTime;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
	protected static $defaultName = 'migratte:status';

	private const OPTION_COMPACT = 'compact';

	protected function configure()
	{
		$this->setDescription('Show migrations status')
			->addOption(self::OPTION_COMPACT, 'c', NULL, 'Show migrations table output in compact mode');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$showCompact = $input->getOption(self::OPTION_COMPACT);

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

			if ($showCompact) {
				$migrations[] = [
					'migrated'  => $this->prepareOutput($isCommitted ? 'YES' : 'NO', $isCommitted ? 'green' : 'yellow'),
					'name'      => ($isBreakpoint ? $this->prepareOutput('BP', ($isCommitted ? 'white' : 'black'), ($isCommitted ? 'red' : 'yellow')) . ' ' : '') . $this->prepareOutput($name, $isCommitted ? 'white' : 'yellow'),
					'committed' => $isCommitted ? $committedAt->format('Y-m-d H:i:s') : '-',
				];
			} else {
				$migrations[] = [
					'no'         => $number,
					'migrated'   => $this->prepareOutput($isCommitted ? 'YES' : 'NO', $isCommitted ? 'green' : 'yellow'),
					'breakpoint' => $isBreakpoint ? $this->prepareOutput(' BP ', ($isCommitted ? 'white' : 'black'), ($isCommitted ? 'red' : 'yellow')) : '',
					'name'       => $this->prepareOutput($name, $isCommitted ? 'white' : 'yellow'),
					'committed'  => $isCommitted ? $committedAt->format('Y-m-d H:i:s') : '-',
					'created'    => $createdAt->format('Y-m-d H:i:s'),
					'file'       => $migration->getFileName(),
				];
			}
		}

		if (count($migrationFiles) > 0) {
			if ($showCompact) {
				$header = [
					'Done',
					'Migration name',
					'Committed at',
				];
			} else {
				$header = [
					'No.',
					'Done',
					'BP',
					'Migration name',
					'Committed at',
					'Created at',
					'File name',
				];
			}
			$table = new Table($output);
			$table->setStyle('box-double')
				->setHeaders($header)
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

		return 0;
	}
}