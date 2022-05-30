<?php

namespace Semisedlak\Migratte\Commands;

use DateTime;
use Exception;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommitCommand extends Command
{
	protected static $defaultName = 'migratte:commit';

	private const DEFAULT_LIMIT = 99999;
	private const ARGUMENT_LIMIT = 'limit';
	private const OPTION_DATETIME_FROM = 'from';
	private const OPTION_DATETIME_TO = 'to';
	private const OPTION_DRY_RUN = 'dry-run';

	protected function configure()
	{
		$this->setDescription('Commit (run) migrations')
			->addArgument(self::ARGUMENT_LIMIT, InputArgument::OPTIONAL, 'Number of migrations to commit', self::DEFAULT_LIMIT)
			->addOption(self::OPTION_DATETIME_FROM, NULL, InputArgument::OPTIONAL, 'Commit migrations from datetime [format "YYYY-MM-DD HH:mm:ss"]', NULL)
			->addOption(self::OPTION_DATETIME_TO, NULL, InputArgument::OPTIONAL, 'Commit migrations to datetime [format "YYYY-MM-DD HH:mm:ss"]', NULL)
			->addOption(self::OPTION_DRY_RUN, 'd', InputArgument::REQUIRED, 'Run in dry-run mode');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$connection = $config->getConnection();
		$table = $config->getTable();
		$migrationsLimit = $input->getArgument(self::ARGUMENT_LIMIT);
		$fromDate = $input->getOption(self::OPTION_DATETIME_FROM);
		$toDate = $input->getOption(self::OPTION_DATETIME_TO);
		$isDryRun = $input->getOption(self::OPTION_DRY_RUN);

		if ($isDryRun) {
			$this->writelnFormatted(' DRY-RUN ', 'black', 'cyan');
		}

		if (!is_null($fromDate)) {
			$fromDate = DateTime::createFromFormat('Y-m-d H:i:s', $fromDate);
			if (!$fromDate) {
				throw new Exception('Date option "from" contains incorrect value');
			}
			$this->writeYellow('Committing from minimal date: ');
			$this->writelnWarning(' ' . $fromDate->format('Y-m-d H:i:s') . ' ');
		}

		if (!is_null($toDate)) {
			$toDate = DateTime::createFromFormat('Y-m-d H:i:s', $toDate);
			if (!$toDate) {
				throw new Exception('Date option "to" contains incorrect value');
			}
			$this->writeYellow('Committing up to maximal date: ');
			$this->writelnWarning(' ' . $toDate->format('Y-m-d H:i:s') . ' ');
		}

		if ($migrationsLimit != self::DEFAULT_LIMIT) {
			$this->writelnCyan('Limiting to ' . $migrationsLimit . ' migration' . ($migrationsLimit != 1 ? 's' : ''));
		}

		$migrationFiles = $this->kernel->getMigrationFilesList();

		$commitPerformed = FALSE;
		$count = 0;
		foreach ($migrationFiles as $migrationFile) {
			require_once $this->kernel->getMigrationPath($migrationFile);

			if ($this->kernel->getMigration($migrationFile)) {
				continue;
			}
			$commitPerformed = TRUE;

			/** @var Migration $migrationClass */
			$migrationClass = $this->kernel->parseMigrationClassName($migrationFile);
			$migrationTimestamp = $this->kernel->parseMigrationTimestamp($migrationFile);
			$created = DateTime::createFromFormat('Ymd_His', $migrationTimestamp);

			if ($fromDate && !$created->diff($fromDate)->invert) {
				continue;
			}
			if ($toDate && $created->diff($toDate)->invert) {
				continue;
			}

			$this->write('Migration "' . $migrationFile . '" commit ... ');
			$count++;

			$connection->begin();
			try {
				if (!$isDryRun) {
					$connection->nativeQuery($migrationClass::up());

					$connection->insert($table->getName(), [
						$table->fileName    => $migrationFile,
						$table->committedAt => new DateTime(),
					])->execute();
				}

				$connection->commit();

				$this->writelnSuccess(' DONE ');
			} catch (Exception $e) {
				$connection->rollback();
				$this->writelnError(' FAILURE ');
				$this->writelnRed($e->getMessage());

				return 2;
			}

			if ($count >= $migrationsLimit) {
				break;
			}
		}

		if (!$commitPerformed) {
			$this->writelnWarning(' No migrations to commit... ');
		}

		return 0;
	}
}