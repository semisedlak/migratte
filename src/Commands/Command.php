<?php

namespace Semisedlak\Migratte\Commands;

use Semisedlak\Migratte\Application\Application;
use Semisedlak\Migratte\Application\Kernel;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method void writeBlack(string|array $message, string $separator = ', ')
 * @method void writeWhite(string|array $message, string $separator = ', ')
 * @method void writeRed(string|array $message, string $separator = ', ')
 * @method void writeGreen(string|array $message, string $separator = ', ')
 * @method void writeBlue(string|array $message, string $separator = ', ')
 * @method void writeCyan(string|array $message, string $separator = ', ')
 * @method void writeMagenta(string|array $message, string $separator = ', ')
 * @method void writeYellow(string|array $message, string $separator = ', ')
 * @method void writelnBlack(string|array $message, string $separator = PHP_EOL)
 * @method void writelnWhite(string|array $message, string $separator = PHP_EOL)
 * @method void writelnRed(string|array $message, string $separator = PHP_EOL)
 * @method void writelnGreen(string|array $message, string $separator = PHP_EOL)
 * @method void writelnBlue(string|array $message, string $separator = PHP_EOL)
 * @method void writelnCyan(string|array $message, string $separator = PHP_EOL)
 * @method void writelnMagenta(string|array $message, string $separator = PHP_EOL)
 * @method void writelnYellow(string|array $message, string $separator = PHP_EOL)
 */
abstract class Command extends SymfonyCommand
{
	protected Kernel $kernel;

	protected InputInterface $input;

	protected OutputInterface $output;

	public function __construct(Kernel $kernel)
	{
		parent::__construct();
		$this->kernel = $kernel;
	}

	/**
	 * @param string              $name
	 * @param array<string|mixed> $arguments
	 * @return mixed|void
	 */
	public function __call($name, $arguments)
	{
		if (substr($name, 0, 7) === 'writeln') {
			$color = strtolower(substr($name, 7));
			/** @var string $text */
			$text = $arguments[1] ?? '';
			$this->writelnFormatted($arguments[0], $color, '', $text ?: PHP_EOL);
		} elseif (substr($name, 0, 5) === 'write') {
			$color = strtolower(substr($name, 5));
			/** @var string $text */
			$text = $arguments[1] ?? '';
			$this->writeFormatted($arguments[0], $color, '', $text ?: ', ');
		}
	}

	public function write(string $message): void
	{
		$this->output->write($message);
	}

	public function writeln(string $message): void
	{
		$this->output->writeln($message);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $foregroundColor
	 * @param string          $backgroundColor
	 * @param string          $separator
	 */
	protected function writelnFormatted(
		$message,
		string $foregroundColor = '',
		string $backgroundColor = '',
		string $separator = PHP_EOL
	): void {
		$output = $this->prepareOutput($message, $foregroundColor, $backgroundColor, $separator);
		$this->output->writeln($output);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $foregroundColor
	 * @param string          $backgroundColor
	 * @param string          $separator
	 *
	 * @return string
	 */
	protected function prepareOutput(
		$message,
		string $foregroundColor = '',
		string $backgroundColor = '',
		string $separator = PHP_EOL
	): string {
		$allowedColors = ['black', 'red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white', 'default'];

		$fg = $foregroundColor != '' && in_array($foregroundColor, $allowedColors) ? 'fg=' . $foregroundColor : '';
		$bg = $backgroundColor != '' && in_array($backgroundColor, $allowedColors) ? 'bg=' . $backgroundColor : '';

		$format = implode(';', array_filter([$fg, $bg]));

		if (is_array($message)) {
			$outputData = [];
			foreach ($message as $msg) {
				$outputData[] = $format != '' ? '<' . $format . '>' . $msg . '</' . $format . '>' : $msg;
			}
			$output = implode($separator, $outputData);
		} else {
			$output = $format != '' ? '<' . $format . '>' . $message . '</' . $format . '>' : $message;
		}

		return $output;
	}

	/**
	 * @param string|string[] $message
	 * @param string          $foregroundColor
	 * @param string          $backgroundColor
	 * @param string          $separator
	 */
	protected function writeFormatted(
		$message,
		string $foregroundColor = '',
		string $backgroundColor = '',
		string $separator = ', '
	): void {
		$output = $this->prepareOutput($message, $foregroundColor, $backgroundColor, $separator);
		$this->output->write($output);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->input = $input;
		$this->output = $output;

		$this->write(Application::NAME . ' ');
		$this->writelnGreen(Application::VERSION . PHP_EOL);

		return 0;
	}

	/**
	 * @param string|string[] $message
	 * @param string          $separator
	 */
	protected function writelnSuccess($message, string $separator = PHP_EOL): void
	{
		$this->writelnFormatted($message, 'black', 'green', $separator);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $separator
	 */
	protected function writeSuccess($message, string $separator = PHP_EOL): void
	{
		$this->writeFormatted($message, 'black', 'green', $separator);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $separator
	 */
	protected function writelnWarning($message, string $separator = PHP_EOL): void
	{
		$this->writelnFormatted($message, 'black', 'yellow', $separator);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $separator
	 */
	protected function writeWarning($message, string $separator = PHP_EOL): void
	{
		$this->writeFormatted($message, 'black', 'yellow', $separator);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $separator
	 */
	protected function writelnError($message, string $separator = PHP_EOL): void
	{
		$this->writelnFormatted($message, 'white', 'red', $separator);
	}

	/**
	 * @param string|string[] $message
	 * @param string          $separator
	 */
	protected function writeError($message, string $separator = PHP_EOL): void
	{
		$this->writeFormatted($message, 'white', 'red', $separator);
	}
}
