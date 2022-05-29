<?php

namespace Semisedlak\Migratte\Commands;

use Exception;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command
{
	protected static $defaultName = 'migratte:rollback';

	private const ARGUMENT_LIMIT = 'limit';

	protected function configure()
	{
		$this->setDescription('Rollback migrations')
			->addArgument(self::ARGUMENT_LIMIT, InputArgument::OPTIONAL, 'Number of migrations to rollback', 1);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$connection = $config->getConnection();
		$table = $config->getTable();
		$migrationsLimit = $input->getArgument(self::ARGUMENT_LIMIT);

		$rollbackPerformed = FALSE;
		$count = 0;
		while ($lastMigration = $this->kernel->getLastMigration()) {
			$rollbackPerformed = TRUE;
			$migrationFile = $lastMigration[$table->fileName];

			$this->write('Migration "' . $migrationFile . '" rollback ... ');
			$className = $this->kernel->parseMigrationClassName($migrationFile);

			require_once $this->kernel->getMigrationPath($migrationFile);

			/** @var Migration $migration */
			$migration = new $className($this->kernel, $migrationFile);

			$connection->begin();
			try {
				if ($migration::isBreakpoint()) {
					throw new Exception('Migration is breakpoint and thus rollback is unable');
				}

				$connection->nativeQuery($migration::down());

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