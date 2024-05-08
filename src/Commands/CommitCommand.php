<?php

namespace Semisedlak\Migratte\Commands;

use DateTime;
use Exception;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CommitCommand extends Command
{
	protected static $defaultName = 'migratte:commit';

	private const DEFAULT_LIMIT = 99999;
	private const ARGUMENT_LIMIT = 'limit';
	private const OPTION_DATETIME_FROM = 'from';
	private const OPTION_DATETIME_TO = 'to';
	private const OPTION_EXCLUSIVE = 'exclusive';
	private const OPTION_DRY_RUN = 'dry-run';

	protected function configure(): void
	{
		$this->setDescription('Commit (run) migrations')
			->addArgument(
				self::ARGUMENT_LIMIT,
				InputArgument::OPTIONAL,
				'Number of migrations to commit',
				self::DEFAULT_LIMIT
			)
			->addOption(
				self::OPTION_DATETIME_FROM,
				null,
				InputArgument::OPTIONAL,
				'Commit migrations from datetime [format "YYYY-MM-DD HH:mm:ss"]',
				null
			)
			->addOption(
				self::OPTION_DATETIME_TO,
				null,
				InputArgument::OPTIONAL,
				'Commit migrations to datetime [format "YYYY-MM-DD HH:mm:ss"]',
				null
			)
			->addOption(
				self::OPTION_EXCLUSIVE,
				'x',
				InputArgument::REQUIRED,
				'Write migration info into database but do not perform migration SQL'
			)
			->addOption(
				self::OPTION_DRY_RUN,
				'd',
				InputArgument::REQUIRED,
				'Run in dry-run mode'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$connection = $config->getConnection();
		$driver = $config->getDriver();
		$table = $driver->getTable();

		/** @var int $migrationsLimit */
		$migrationsLimit = $input->getArgument(self::ARGUMENT_LIMIT);
		/** @var string|null $fromDate */
		$fromDate = $input->getOption(self::OPTION_DATETIME_FROM);
		/** @var string|null $toDate */
		$toDate = $input->getOption(self::OPTION_DATETIME_TO);
		$isExclusive = $input->getOption(self::OPTION_EXCLUSIVE);
		$isDryRun = $input->getOption(self::OPTION_DRY_RUN);

		if ($isDryRun) {
			$this->writelnFormatted(' DRY-RUN ', 'black', 'cyan');
		}
		if ($isExclusive) {
			$this->writelnFormatted(' EXCLUSIVE ', 'black', 'magenta');

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion($this->prepareOutput('Are you sure to perform commit in "exclusive" mode? [y/N]: ', 'yellow'), false);
			/** @var bool|null $confirmed */
			$confirmed = $helper->ask($this->input, $this->output, $question);

			if (!$confirmed) {
				$this->writeln("\nAborted. No operation performed.");

				return 0;
			}
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

		$group = $driver->getNextGroupNo();

		$migrationFiles = $this->kernel->getMigrationFilesList();

		$commitPerformed = false;
		$count = 0;
		foreach ($migrationFiles as $migrationFile) {
			require_once $this->kernel->getMigrationPath($migrationFile);

			$migration = $driver->getMigrationByFileName($migrationFile);
			$committedAt = $driver->getMigrationCommittedAt($migration, $config->getTimeZone());
			if ($migration && $committedAt) {
				continue;
			}
			$commitPerformed = true;

			/** @var Migration $migrationClass */
			$migrationClass = $this->kernel->parseMigrationClassName($migrationFile);
			$migrationTimestamp = $this->kernel->parseMigrationTimestamp($migrationFile);
			$created = DateTime::createFromFormat('Ymd_His', $migrationTimestamp);
			if (!$created) {
				throw new Exception(sprintf('Cannot parse migration %s timestamp', $migrationFile));
			}

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
					if (!$isExclusive) {
						$tempFile = tempnam(sys_get_temp_dir(), 'migration_');
						if (!$tempFile) {
							throw new Exception('Cannot create temporary file');
						}
						file_put_contents($tempFile, $migrationClass::up());

						// This execute migration SQL
						$connection->loadFile($tempFile);

						@unlink($tempFile);
					}

					$driver->commitMigration($migrationFile, $group);
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
