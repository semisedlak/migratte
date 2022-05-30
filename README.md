Migratte
========
Simple SQL migrations management standalone CLI app, framework-agnostic. It is inspired by [Phinx](https://phinx.org/), but it is much simpler, and it doesn't require CakePHP framework to be installed.

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

Specific migration class extends from basic migration class. It contains timestamp in file name and class name witch should not be modified. 

Migration file that doesn't contain "down" method is "breakpoint" - rollback cannot be performed. BUT! There is an option to perform rollback with this kind of migration.

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

use Semisedlak\Migratte\Migrations\Application;

require __DIR__ . '/../vendor/autoload.php';

(Application::boot())->run();
```

Don't forget to change permission using `chmod +x bin/migrations`

## Commands
When you run `bin/migrations` in CLI you will see "help" overview with possible commands.

```
root@12345:/var/www/html# bin/migrations       
Migratte 0.1.0

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

### `migratte:generate`
Command generates new migration file which then can be modified.

### `migratte:commit`
Command commits (runs) new migrations. By default, it will run all non-committed migrations one-by-one. You can specify less to commit with "limit" argument:

```shell
$ bin/migrations migratte:commit 3
```

But there is more. You can specify datetime limits "from" and "to" for limiting committing.

### `migratte:rollback`
Command performs rollback operation to already committed migrations back to previous state. Rollback will be done only on (by default) one lastly committed migration. You can specify more migrations to rollback with "limit" argument:

```shell
$ bin/migrations migratte:rollback 3
```

If migration doesn't contain "down" method or this method simply returns NULL or FALSE it is considered as "breakpoint". Calling rollback on "breakpoint" will throw an error. This can be bypassed by using "--force" (or "-f") option.

### `migratte:status`
Command shows current migrations' status (what is or isn't committed, which migration is breakpoint and more).

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