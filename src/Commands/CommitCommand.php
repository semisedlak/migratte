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

	private const ARGUMENT_LIMIT = 'limit';

	protected function configure()
	{
		$this->setDescription('Commit (run) migrations')
			->addArgument(self::ARGUMENT_LIMIT, InputArgument::OPTIONAL, 'Number of migrations to commit', 99999);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		$config = $this->kernel->getConfig();
		$connection = $config->getConnection();
		$table = $config->getTable();
		$migrationsLimit = $input->getArgument(self::ARGUMENT_LIMIT);

		$migrationFiles = $this->kernel->getMigrationFilesList();

		$commitPerformed = FALSE;
		$count = 0;
		foreach ($migrationFiles as $migrationFile) {
			require_once $this->kernel->getMigrationPath($migrationFile);

			if ($this->kernel->getMigration($migrationFile)) {
				continue;
			}
			$commitPerformed = TRUE;

			$this->write('Migration "' . $migrationFile . '" commit ... ');
			/** @var Migration $migrationClass */
			$migrationClass = $this->kernel->parseMigrationClassName($migrationFile);

			$connection->begin();
			try {
				$connection->nativeQuery($migrationClass::up());

				$connection->insert($table->getName(), [
					$table->fileName    => $migrationFile,
					$table->committedAt => new DateTime(),
				])->execute();

				$connection->commit();

				$this->writelnSuccess(' DONE ');
				$count++;
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