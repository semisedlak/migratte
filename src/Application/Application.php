<?php

namespace Semisedlak\Migratte\Application;

use ReflectionClass;
use ReflectionException;
use Semisedlak\Migratte\Commands;
use Symfony\Component\Console\Application as ConsoleApplication;

class Application
{
	public const NAME = 'Migratte';
	public const VERSION = '0.5.1';

	/**
	 * @param array<string|string[]> $options
	 * @param array<class-string>    $commandClasses
	 * @return ConsoleApplication
	 * @throws ReflectionException
	 */
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

			/** @var Commands\Command $command */
			$command = new $commandClass($kernel);
			$application->add($command);
		}

		return $application;
	}
}
