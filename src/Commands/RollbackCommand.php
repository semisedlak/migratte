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

	protected function configure()
	{
		$this->setDescription('Rollback migrations')
			->addArgument(self::ARGUMENT_LIMIT, InputArgument::OPTIONAL, 'Number of migrations to rollback', 1)
			->addOption(
				self::OPTION_STRATEGY,
				NULL,
				InputArgument::OPTIONAL,
				'Rollback strategy (by commit "' . Kernel::ROLLBACK_BY_DATE . '" or by migration "' . Kernel::ROLLBACK_BY_ORDER . '")',
				Kernel::ROLLBACK_BY_DATE
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

		if (!in_array($rollbackStrategy, [
			Kernel::ROLLBACK_BY_ORDER,
			Kernel::ROLLBACK_BY_DATE,
		])) {
			throw new Exception('Unknown rollback strategy: ' . $rollbackStrategy);
		}

		$this->writelnCyan('Rollback strategy: ' . $rollbackStrategy);

		$rollbackPerformed = FALSE;
		$count = 0;
		while ($lastMigration = $this->kernel->getLastMigration($rollbackStrategy)) {
			$rollbackPerformed = TRUE;
			$migrationFile = $lastMigration[$table->fileName];
			require_once $this->kernel->getMigrationPath($migrationFile);

			$this->write('Migration "' . $migrationFile . '" rollback ... ');
			/** @var Migration $migrationClass */
			$migrationClass = $this->kernel->parseMigrationClassName($migrationFile);

			$connection->begin();
			try {
				if ($migrationClass::isBreakpoint()) {
					throw new Exception('Migration is breakpoint and thus rollback is unable');
				}

				$connection->nativeQuery($migrationClass::down());

				$connection->delete($table->getName())
					->where('%n = %i', $table->primaryKey, $lastMigration[$table->primaryKey])
					->execute();

				$connection->commit();

				$this->writelnSuccess(' DONE ');
				$count++;
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