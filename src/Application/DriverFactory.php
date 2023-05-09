<?php

namespace Semisedlak\Migratte\Application;

use Dibi\Connection;
use Dibi\Drivers\MySqliDriver as DibiMySqliDriver;
use Dibi\Drivers\PostgreDriver as DibiPostgreDriver;
use Dibi\Drivers\SqliteDriver as DibiSqliteDriver;
use Semisedlak\Migratte\Drivers\MysqlDriver;
use Semisedlak\Migratte\Drivers\PostgreDriver;
use Semisedlak\Migratte\Drivers\SqliteDriver;

class DriverFactory
{
	public static function create(Connection $connection): IDriver
	{
		$driver = $connection->getDriver();

		if ($driver instanceof DibiSqliteDriver) {
			return new SqliteDriver($connection);
		}

		if ($driver instanceof DibiMySqliDriver) {
			return new MysqlDriver($connection);
		}

		if ($driver instanceof DibiPostgreDriver) {
			return new PostgreDriver($connection);
		}

		throw new \InvalidArgumentException(sprintf("Unknown driver %s", get_class($driver)));
	}
}
