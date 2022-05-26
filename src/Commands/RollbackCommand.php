<?php

namespace Semisedlak\Migratte\Commands;

use Exception;
use Semisedlak\Migratte\Migrations\Migration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command
{
	private const ARGUMENT_LIMIT = 'limit';

	protected function configure()
	{
		$this->setName('migratte:rollback')
			->setDescription('Rollback migrations')
			->addArgument(self::ARGUMENT_LIMIT, InputArgument::OPTIONAL, 'Number of migrations to rollback', 1);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$connection = $config->getConnection();
		$migrationsLimit = $input->getArgument(self::ARGUMENT_LIMIT);

		$rollbackPerformed = FALSE;
		$count = 0;
		while ($lastMigration = $this->kernel->getLastMigration()) {
			$rollbackPerformed = TRUE;
			$migrationFile = $lastMigration->file;
			$this->write('Migration "' . $migrationFile . '" rollback ... ');
			$className = $this->kernel->parseMigrationClassName($migrationFile);
			require_once $config->migrationsDir . '/' . $migrationFile;

			/** @var Migration $migration */
			$migration = new $className($this->kernel);

			$connection->begin();
			try {
				if ($migration::isBreakpoint()) {
					throw new Exception('Migration is breakpoint and thus rollback is unable');
				}

				$connection->nativeQuery($migration::down());

				$connection->delete($config->migrationsTable)
					->where('[id] = %i', $lastMigration->id)
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