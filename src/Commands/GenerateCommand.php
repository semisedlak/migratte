<?php

namespace Semisedlak\Migratte\Commands;

use DateTime;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GenerateCommand extends Command
{
	protected static $defaultName = 'migratte:generate';

	private const ARGUMENT_NAME = 'name';

	protected function configure(): void
	{
		$this->setDescription('Generate new migration file')
			->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'Migration name');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		parent::execute($input, $output);

		/** @var string|null $originalName */
		$originalName = $this->input->getArgument(self::ARGUMENT_NAME);
		$name = $this->getMigrationName($originalName);
		$this->writeln('');

		$now = new DateTime;
		$now->setTimezone($this->kernel->getConfig()->getTimeZone());
		$nowClassName = $now->format('Ymd_His');
		$contents = $this->getMigrationTemplate($name, $now);
		$modifiedName = $this->prepareName($name);

		$filename = "$nowClassName-$modifiedName.php";
		$this->write('Generating migration file "' . $filename . '" ... ');
		file_put_contents($this->kernel->getMigrationPath($filename), $contents);

		$this->writelnSuccess(' DONE ');

		return 0;
	}

	private function getMigrationName(?string $name = null, bool $useArgument = true): string
	{
		/** @var string|null $name */
		$name = $useArgument ? $name : null;

		if (!$name) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new Question($this->prepareOutput('Migration name: ', 'cyan'));
			/** @var string|null $name */
			$name = $helper->ask($this->input, $this->output, $question);
			if (!$name) {
				$this->writelnWarning(' Invalid name entered! ');
				/** @var string $name */
				$name = $this->getMigrationName(null, false);
			}
		}

		$name = addslashes($name);

		return $name;
	}

	private function getMigrationTemplate(string $name, DateTime $date): string
	{
		$nowClassName = $date->format('Ymd_His');

		return <<<PHP
<?php

use Semisedlak\Migratte\Migrations\Migration;

class Migration_$nowClassName extends Migration
{
	public static function getName(): string
	{
		return '$name';
	}

	public static function up(): string
	{
		return <<<SQL
-- UP: $name
SQL;
	}

	public static function down(): ?string
	{
		return NULL;
	}
}
PHP;
	}

	private function prepareName(?string $name): ?string
	{
		if ($name) {
			$name = preg_replace('#[^a-z\d]+#i', '-', strtolower($name));
			if ($name) {
				$name = trim($name, '-');
			}
		}

		return $name;
	}
}
