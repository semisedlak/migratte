<?php

namespace Semisedlak\Migratte\Commands;

use Exception;
use Semisedlak\Migratte\Migrations\Kernel;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command
{
	protected static $defaultName = 'migratte:rollback';

	private const ARGUMENT_LIMIT = 'limit';
	private const OPTION_STRATEGY = 'strategy';
	private const OPTION_FILE = 'file';
	private const OPTION_FORCE = 'force';
	private const OPTION_DRY_RUN = 'dry-run';

	protected function configure()
	{
		$this->setDescription('Rollback migrations')
			->addArgument(
				self::ARGUMENT_LIMIT,
				InputArgument::OPTIONAL,
				'Number of migrations to rollback',
				1
			)
			->addOption(
				self::OPTION_FILE,
				null,
				InputArgument::OPTIONAL,
				'File name of migrations to rollback',
				''
			)
			->addOption(
				self::OPTION_STRATEGY,
				null,
				InputArgument::OPTIONAL,
				'Rollback strategy (by commit "' . Kernel::ROLLBACK_BY_DATE . '" or by migration "' . Kernel::ROLLBACK_BY_ORDER . '" or by specific "' . Kernel::ROLLBACK_BY_FILE . '")',
				Kernel::ROLLBACK_BY_DATE
			)
			->addOption(
				self::OPTION_FORCE,
				'f',
				InputArgument::REQUIRED,
				'Run in forced mode (breakpoint migrations will be removed from evidence)'
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
		$table = $config->getTable();
		$migrationsLimit = $input->getArgument(self::ARGUMENT_LIMIT);
		$rollbackStrategy = $input->getOption(self::OPTION_STRATEGY);
		$migrationFileName = $input->getOption(self::OPTION_FILE);
		$isForced = $input->getOption(self::OPTION_FORCE);
		$isDryRun = $input->getOption(self::OPTION_DRY_RUN);

		if ($isDryRun) {
			$this->writelnFormatted(' DRY-RUN ', 'black', 'cyan');
		}

		if (!in_array($rollbackStrategy, [
			Kernel::ROLLBACK_BY_ORDER,
			Kernel::ROLLBACK_BY_DATE,
			Kernel::ROLLBACK_BY_FILE,
		])) {
			throw new Exception('Unknown rollback strategy: ' . strtoupper($rollbackStrategy));
		}
		if ($migrationFileName) {
			$rollbackStrategy = Kernel::ROLLBACK_BY_FILE;
			$extension = strtolower(substr($migrationFileName, -4, 4));
			if ($extension !== '.php') {
				$migrationFileName .= '.php';
			}
		}

		$this->writeCyan('Using rollback strategy: ' . strtoupper('by ' . $rollbackStrategy) . ' ');
		$this->writeln($migrationFileName);
		$this->writeln('');

		if ($rollbackStrategy === Kernel::ROLLBACK_BY_FILE && !$migrationFileName) {
			$this->writelnError(' No file name provided! ');
			$this->writeln('');
			$this->writeCyan('Tip: When rollbacking specific file use following syntax: ');
			$this->writeln('migratte:rollback --file=migration_file.php');

			return 2;
		}

		$rollbackPerformed = false;
		$count = 0;
		$migrations = $this->kernel->getAllMigrations($rollbackStrategy, $migrationFileName);
		foreach ($migrations as $migration) {
			$rollbackPerformed = true;
			$migrationFile = $migration[$table->fileName];
			require_once $this->kernel->getMigrationPath($migrationFile);

			/** @var Migration $migrationClass */
			$migrationClass = $this->kernel->parseMigrationClassName($migrationFile);

			$this->write('Migration "' . $migrationFile . '" rollback ... ');
			$count++;

			$connection->begin();
			try {
				if ($migrationClass::isBreakpoint()) {
					if ($isForced) {
						$this->writeWarning(' FORCED BREAKPOINT ');
						$this->write(' ');
					} else {
						throw new Exception('Migration is breakpoint and thus rollback is unable');
					}
				}

				if (!$isDryRun) {
					$downSql = $migrationClass::down();
					if ($downSql) {
						$tempFile = tempnam(sys_get_temp_dir(), 'migration_');
						file_put_contents($tempFile, $downSql);

						$connection->loadFile($tempFile);
					} else {
						$this->writeFormatted(' NO DOWN SQL ', 'black', 'cyan');
						$this->write(' ');
					}

					$connection->delete($table->getName())
						->where('%n = %i', $table->primaryKey, $migration[$table->primaryKey])
						->execute();

					if ($downSql) {
						@unlink($tempFile);
					}
				}

				$connection->commit();

				$this->writelnSuccess(' DONE ');
			} catch (Exception $e) {
				$connection->rollback();
				$this->writelnError(' FAILURE ');
				$this->writelnRed($e->getMessage());

				return 3;
			}

			if ($count >= $migrationsLimit) {
				break;
			}
		}

		if (!$rollbackPerformed) {
			$this->writelnWarning(' No migration to rollback... ');
		}

		return 0;
	}
}
