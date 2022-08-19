<?php

namespace Semisedlak\Migratte\Migrations;

use ReflectionClass;
use Semisedlak\Migratte\Commands;
use Symfony\Component\Console\Application as ConsoleApplication;

class Application
{
	public const NAME = 'Migratte';
	public const VERSION = '0.2.0';

	public static function boot(array $options = [], array $commandClasses = []): ConsoleApplication
	{
		$application = new ConsoleApplication(self::NAME, self::VERSION);

		if (empty($commandClasses)) {
			$commandClasses = [
				Commands\GenerateCommand::class,
				Commands\CommitCommand::class,
				Commands\RollbackCommand::class,
				Commands\StatusCommand::class,
				Commands\InfoCommand::class,
			];
		}

		$config = new Config($options);
		$kernel = new Kernel($config, $commandClasses);

		foreach ($commandClasses as $commandClass) {
			$reflection = new ReflectionClass($commandClass);
			if (!$reflection->getParentClass() || $reflection->getParentClass()->getName() != Commands\Command::class) {
				echo 'Class "' . $commandClass . '" is not child class of "' . Commands\Command::class . '"' . PHP_EOL;
				echo 'Please, remove this command...' . PHP_EOL;
				exit(1);
			}

			$application->add(new $commandClass($kernel));
		}

		return $application;
	}
}