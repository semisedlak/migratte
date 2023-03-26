Migratte
========

- [How it works](#how-it-works)
- [Migration anatomy](#migration-anatomy)
- [Configuration](#configuration)
- [Executable file](#executable-file)
- [Commands](#commands)
  - [`migratte:generate`](#migrattegenerate)
  - [`migratte:commit`](#migrattecommit)
  - [`migratte:rollback`](#migratterollback)
  - [`migratte:status`](#migrattestatus)
  - [`migratte:info`](#migratteinfo)
- [Are you using Nette Framework?](#are-you-using-nette-framework)
- [Changelog](#changelog)
  - [0.4.0](#040)

Migratte is simple SQL migrations management standalone CLI app, framework-agnostic. It is inspired by [Phinx](https://phinx.org/), but it is much simpler, and it doesn't require CakePHP framework to be installed.

You can install it using composer:

```shell
$ composer require semisedlak/migratte
```

## How it works
It is controlled by ([Symfony Console](https://github.com/symfony/console)) CLI commands for:

1. generate new migration
2. commit migration
3. rollback migration
4. showing migrations status
5. showing configuration info

## Migration anatomy
Migrations are simple PHP files keeping plain SQL queries. If plain SQL so why PHP files then? Answer is simple. For keeping "up" (for commit) and "down" (for rollback) SQL queries with additional metadata all together.

> 💡 Migratte doesn't provide (yet) rich migrations management. It is just a simple tool for executing your SQL queries and it is up to you to write them. You can use any SQL query you want.
>
> You can still use [Phinx](https://phinx.org/) with [CakePHP Migrations](https://book.cakephp.org/3.0/en/migrations.html) for migrations management. I recommend [Adminer](https://www.adminer.org/) for database management.

Specific migration class extends from basic migration class. It contains timestamp in file name and class name witch should not be modified.

Migration file that doesn't contain "down" method is "breakpoint", that means rollback cannot be performed. BUT! There is an option `--force` to perform rollback with this kind of migration.

This tool creates it's "memory" in same database as migrations target. It uses [dibi database layer](https://dibiphp.com/) for connection and queries executions.

You can use it with:
* MySQL database
* PostgreSQL database
* SQLite database

## Configuration
You can override this default configuration options:

```php
'timezone'        => 'UTC',
'migrationsDir'   => "$workingDir/database/migrations",
'migrationsTable' => [
  'name'        => 'migrations',
  'primaryKey'  => 'id',
  'fileName'    => 'filename',
  'committedAt' => 'committed_at',
],
'connection'      => [
  'driver'   => 'sqlite', // mysqli or postgre
  'database' => "$workingDir/database/migratte.s3db",
],
```

providing options array as first argument of `Application::boot()` method from executable file. You can read how to set up connection in [dibi documentation](https://dibiphp.com/).

## Executable file
Create a file `bin/migrations` in the root dir of your project with following content:

```php
#!/usr/bin/env php
<?php

use Semisedlak\Migratte\Application\Application;

require __DIR__ . '/../vendor/autoload.php';

(Application::boot())->run();
```

Don't forget to change permission using `chmod +x bin/migrations`

## Commands
When you run `bin/migrations` in CLI you will see "help" overview with possible commands.

```
root@12345:/var/www/html# bin/migrations
Migratte 0.4.0

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help               Display help for a command
  list               List commands
 migratte
  migratte:commit    Commit (run) migrations
  migratte:generate  Generate new migration file
  migratte:info      Show migrations configuration info
  migratte:rollback  Rollback migrations
  migratte:status    Show migrations status
```

> 💡 Hint: You can use `--help` (or `-h`) option for each command to see more details.

### `migratte:generate`
Command generates new migration file which then can be modified. You can specify migration name as first argument. Write it as normal sentence, it will be converted to desired form automatically.

```shell
$ bin/migrations migratte:generate "Create users table"
```

This will generate file `database/migrations/20190101_120000-create-users-table.php` with following content:

```php
<?php

use Semisedlak\Migratte\Migrations\Migration;

class Migration_20190101_120000 extends Migration
{
	public static function getName(): string
	{
		return 'Create users table';
	}

	public static function up(): string
	{
		return <<<SQL
-- UP: Create users table
SQL;
	}

	public static function down(): ?string
	{
		return NULL;
	}
}
```

> ⚠️ Warning! Don't modify migration class name. Don't modify file name after it was committed. You can modify `up()` and `down()` methods to contain your SQL queries.

If you want to change migration name you can change it in `getName()` method. It is used only for displaying purposes.

Then copy your SQL queries to `up()` and `down()` methods. If `down()` method returns NULL or FALSE it is considered as "breakpoint" migration (it cannot be rollbacked because it doesn't provide "down" operation).

```php
public static function up(): string
{
	return <<<SQL
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL
) ENGINE='InnoDB' COLLATE 'utf8mb4_general_ci';
SQL;
}

public static function down(): ?string
{
	return <<<SQL
DROP TABLE `users`;
SQL;
}
```

### `migratte:commit`
Command commits (runs) new migrations. By default, it will run all non-committed migrations one-by-one. You can specify less to commit with `limit` (first) argument:

```shell
$ bin/migrations migratte:commit 3
```

But there is more. You can specify datetime limits `--from` and `--to` for limiting committing.

Are you unsure what migrations will be committed? Use `--dry-run` (or `-d`) option to see what migrations will be committed without actually committing them.

### `migratte:rollback`
Command performs rollback operation to already committed migrations back to previous state. Rollback will be done only on (by default) one lastly committed migration. You can specify more migrations to rollback with `limit` (first) argument:

```shell
$ bin/migrations migratte:rollback 3
```

If migration doesn't contain "down" method or this method simply returns NULL or FALSE it is considered as "breakpoint". Calling rollback on "breakpoint" will throw an error. This can be bypassed by using `--force` (or `-f`) option.

#### Rollback strategy

You can specify rollback strategy by using `--strategy` option. There are currently three strategies for rollback:

1. by commit **"date"** (this is default) (`--strategy=date`) - rollback by commit date (last commited migration will be rollbacked first)
2. by migration **"order"** (`--strategy=order`) - rollback migrations by migrations order (if you sort files by name, you will get migrations order, so last commited file will be rollbacked first)
3. by specific **"file"** (`--strategy=file`) - rollback specific migration file. You have to provide migration file name (without path) as `--file` option. Hint: you can omit `.php` extension.

### `migratte:status`
Command shows current migrations' status (what is or isn't committed, which migration is breakpoint and more).

There is also an option `--compact` (or `-c`) to show compact table of migrations.

### `migratte:info`
Command shows current Migratte configuration.

## Are you using Nette framework?
**Great!** You can use Nette DI extension to register a Migratte panel into [Tracy](https://tracy.nette.org/) (something like `migratte:status` and `migratte:info` combined but... in HTML... with styles). It will use "dibi" extension for connection definition.

```neon
extensions:
    migratte: Semisedlak\Migratte\Nette\DiExtension

migratte:
    debug: true
```

## Changelog

### 0.4.0

Please update your `bin/migrations` executable file `Application` class namespace like this:

```php
// use Semisedlak\Migratte\Migrations\Application; // OLD NAMESPACE
use Semisedlak\Migratte\Application\Application; // NEW NAMESPACE
```
