<?php

namespace Semisedlak\Migratte\Tracy;

use Semisedlak\Migratte\Migrations\Application;
use Semisedlak\Migratte\Migrations\Config;
use Semisedlak\Migratte\Migrations\Kernel;
use Semisedlak\Migratte\Migrations\Migration;
use Tracy\Debugger;
use Tracy\Helpers;
use Tracy\IBarPanel;

class Panel implements IBarPanel
{
	private Kernel $kernel;

	/**
	 * @var Migration[]
	 */
	private array $migrations = [];

	private int $migrationsCount = 0;

	private int $nonCommittedMigrationsCount = 0;

	private float $preparationTime;

	public function __construct($extensionConfig = NULL)
	{
		Debugger::timer('migratte');
		$extensionConfig = json_decode(json_encode($extensionConfig), JSON_OBJECT_AS_ARRAY);

		$config = new Config($extensionConfig);
		$this->kernel = new Kernel($config);

		foreach ($this->kernel->getMigrationFilesList() as $migrationFile) {
			$className = $this->kernel->parseMigrationClassName($migrationFile);
			$committedAt = $this->kernel->getCommittedAt($migrationFile);

			require_once $this->kernel->getMigrationPath($migrationFile);

			/** @var Migration $migration */
			$migration = new $className($this->kernel, $migrationFile, $committedAt);

			$this->migrations[] = $migration;

			if (!$migration->isCommitted()) {
				$this->nonCommittedMigrationsCount++;
			}
		}

		$this->migrations = array_reverse($this->migrations, TRUE);
		$this->migrationsCount = count($this->migrations);
		$this->preparationTime = round(Debugger::timer('migratte') * 10000) / 10;
	}

	public function getTab()
	{
		$appName = Application::NAME . ' ' . Application::VERSION;

		$label = '';
		$icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA7EAAAOxAGVKw4bAAAE8klEQVRYw8WXTUxTaRSGn3tbaikFawewA4FBLCANE5FxiBI0IiPxhxgSF65kIQvQDQmJe3cuICxYGY2O4gozxkSUQTRRIxpCFBGYCnLFEpX/KVBaqPRvFi2VC5eRH6NvctN+9+Se95z3O+c79wrqv9Qmn9/3Z4DAH4Ca7wOvgPBQJarKBOGm8HeAwOHvRCyDgNAscBPPd8x8mRLqr5GrBBW5W3LZF7eP7M3ZmPVmEiITMGqMaFVaANw+N/Z5O0NzQ0hOic7pTp6OP6V9sh1fwPd/7tUCNwmEl/7Qrwhxm+I4l36O0l9KidfGryu9MfcY9YP1VL+tZnxuHMQQh7h4GxYHEMIe4x7u5t/FqDF+E53t83aKW4tps7cts4mylT941e6s/WbkAEaNkdqdtXKVFQMQg1fXdNc3I19A2KeccekyiMrOSur66/D6vRsm9vq91PXXUdlZqWhXLsIQkqKSOPXLKY4nHCfHkINaXF23ev1eOqY6uDN0hxuDN/jg+rBi2ooBnE49TcOHBlweV9gUGRGJJdpCRnTGlzYUQ23o/9KGfTN9WGeszHnmws9GRURxMukkVweufiWAEHqKejBpTdTb6rn16Rbt9nY8Ac+apI8QIsg15nIi8QSlKaWMuEfIasla3Rb0HO7BEmMJ3571ztI51YnVYcU2a2PEPcKUZwq3zw2AVqXFEGHApDWRokvBEmMh25CNTq0L+7A6rGQ1Zy1TQL6pIUOvo1cWgE6tIy82j7zYvDWpsBi9jl7Fklc8B8o7ymkablo32VI0DTdR3lEe9v/VLUAEAYH82HzKUso4+vNRYjfFrol04vMETcNNXLFdoXWilYA/sPqjuGhrEQ9GHxAImQQEdkTvYJdhl3wYKXSB5JR4NfWK3ple2fOHth6iZbRlWaArFmHZizISIxN5NvGM0c+jculElkm57F5ooB0zHaNxuJEnB54Eu2CJAopFCCA5Je7l3+P8P+fRqXVoRA3XbdfZ+9Nebn+6TbohnY9zH9GKWrQqLU6vk8L4QhqHG6n+tZrn/z4nZ0sO+2P382bmjSLH8mWoSKwOK/6AH6PGiOSUOBh3EHOUmcL4Qq79fg2NqKG7qJuCuAIu/3aZC1kXOGI6Qn1uPSIiFdsrcPvdmPVmJKdEmj6NobkheZ0pBhAaRuUvyzHrzQD0O/uDjlxS2GGqPlVucwZtNpeNZF0yoiDKbB6/hzMdZ1ahQAiTnkm6pruoeFnBkHuIZF0yklMiPTo9nJE/4MfmspGqT5UFl6ZPA2DAORDshJEmGj428M71TrFb5DUQkidjcwZ9031cencJgKR7Sbj9bmLUMTi8DtSCmpbRFvRqPZJTonu6mxxDDo/HHzM4O0jJsxJcHhcHHh0Iu17wuTTtFbvg7cxbLg5c5NHYIzy+tc2BBUSoIiiIL+Ds9rOY9ebVH8UAJYkllCSWMDU/RetEKy8mX2B1WHnves/I5xHs83bZLDBqjJg2mdgWtQ1LjIXdW3aTH5uPQWMAgoWttOGKW+DwOMK3DBoDxQnFFCcUr0uFBTg8DtlJq5Az4S6oel2Ffd6+IcLFsM/bqXpdFfa/guiEz4G2iTYy72dS01fDmHts3cRj7jFq+mrIvJ9J20Tb2obRwn+VaoMfJj6ffAgpDKMf+mkmCggPfxA5AsJDUSWqygSEZmDj7+BryFxAaFaJqrL/APRKNBwGXCbEAAAAAElFTkSuQmCC';
		if ($this->nonCommittedMigrationsCount > 0) {
			$label = $this->nonCommittedMigrationsCount . ' migration' . ($this->nonCommittedMigrationsCount > 1 ? 's' : '') . ' / ';
			$icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA7EAAAOxAGVKw4bAAAEJUlEQVRYw8WXT0hUexTHP+fOOKlhoiIM1ZS4KKMoU5uiopBe8MjATboQWkm7QMhlmxCmNrmQVtHiLVqVFAjWkzKimMCgSKQoK6aRKTFCS0VrdOaet/iN88e5Y+oTO3CZuff8fufP955zvr8rcb/fK/H4P8BfqLpZDxGJAf3qcrWKXVv7L6p/r4vj7ED6xK6tnV+3zB2QcP/OubpcsHs3Ul0NO3eCzwfl5bBpE2zYYBZFozA1Bd++QSQCw8Po4CC8eYPE40sYV7fYNTW6cG+r+WuJoCUlyNmzcPo0lJauLsOJCejtRW/eRCcmsESwVbFEUiCkB5CUPXugqwuKi9cG6slJaGuD16+zVFb6ja1qUGhvXzvnYGy1t2eg7BiAJWLg+fBh7ZwvSMJmOvwAjgWoV68i0Sg0NYH7fzZILAbd3ei1a4iD2rEIk0qvFzl1Co4fh6qq5QcTi8G7d/DkCXr/Pjo2lqHOWYTJLmhsRB8+RGdnUwvz86GyEtm+fck21JERCIXQX79SewsLkZMnsXt6lg4gKbdvQ1kZ3LuHPnpk+jkWWxHy6nab+XHiBDQ0wPg4NDdnrcvANIkAmMptaUFaWuDnT3j/HkIhGB01xqanYW7ObPR4oKjIBL15s0Fqxw4oKEgZHx/PmDOOASQV4TBUVqYUBQWwb5+5VivhcFYHJJNNR8BWRQMBCAZX72yxBINoIJCaM2mSexSLQHU10tgIR45AScnKnH7/Ds+eoT09MDiI2vYKRvGhQ+jz50giIBWBigpkMRl5PGb93FwGGenwMITDGfvl4EEYGMhy5YxAdzfa0YGUl6NDQ0haAS0glDVSFz1LEtrRo+jTp8iNG9DcnIWAcxGCodWuLuT6dcjPx8rLQ3t7kb170cePzTz4+tXMAo8HnZ3F8vuNs7Y2GBpCqqqgpgY+fXL2QS4yCoVA1bRiJAIHDoDPh/j9cOkS5OUht24hdXXIxYvI+fPI4cPQ0QGWBWfOmOHk80Ekgvh85vWwTDLSy5fNZkAjkaSh5O+WLc660VHwesGyMnQaj6NXrvwegWRhTE0Z9goETORer3GybVsqI9s2DrduzQhOEoHz5Qv8+AHBIPLgAfL5s2OzOE5CqahAw2H07l2jaGiAaBTZuBFmZgwpDQxAYaHJ8ONH875fvkTHxpALF9DZWfTcuVRSCZuLUcjZBYyMwJ076IsX6Pw8qxHJy0Pq6gyt+3zYTU1ZAeTugvp6qK9HpqeRV6/g7VvDcgtcMDmZyQXFxVBWhiS4gF27YP9+wxEAoZDjKHYmo5mZ1MOiIjh2zFzgeKhYlszMOJKR85Gss9NkuFYyOQmdnSn76a8p14lISkvX/FienuiSAaSPVVk4WKzyw0RjsQwSyiajP/xpZgH9f8S5kX5LXa5WRPoSn8zrljkifepytf4HtbkXfTwanNkAAAAASUVORK5CYII=';
		}
		$label .= $this->preparationTime . ' ms';

		return <<<HTML
<span title="$appName">
	<img src="$icon" width="16" height="16" alt="icon">
	<span class="tracy-label">$label</span>
</span>
HTML;
	}

	public function getPanel()
	{
		$html = $this->getHeadingPart();
		$html .= $this->getMigrationsTable();
		$html .= $this->getSettingsTable();

		return $html;
	}

	private function getHeadingPart(): string
	{
		$appName = Application::NAME;
		$stat = 'migration' . ($this->migrationsCount != 1 ? 's' : '');
		$label = $this->nonCommittedMigrationsCount . ' migration' . ($this->nonCommittedMigrationsCount != 1 ? 's' : '');

		return <<<HTML
<h1>$appName</h1>
<p>There is {$this->migrationsCount} $stat. Database is <strong>$label</strong> behind...</p>
HTML;
	}

	private function getMigrationsTable(): string
	{
		$html = '';
		if ($this->migrationsCount > 0) {
			$html .= <<<HTML
<div class="tracy-inner">
<table style="width:100%;">
<thead>
	<tr>
		<th>&uarr;&nbsp;Migration name</th>
		<th style="text-align:center;">
			<abbr title="Breakpoint (if rollback is possible)">BP</abbr>
		</th>
		<th style="width:100%;">File</th>
	</tr>
</thead>
<tbody>
<tr></tr>
HTML;

			/** @var Migration $migration */
			foreach ($this->migrations as $key => $migration) {
				$committedDate = $migration->isCommitted() ? $migration->getCommittedAt()->format('Y-m-d H:i:s') : '';
				$editorLink = Helpers::editorUri($this->kernel->getMigrationPath($migration->getFileName()));
				if ($migration::isBreakpoint()) {
					$breakpoint = '<abbr title="Rollback cannot be performed on migration" style="color:red;border:none;">Yes</abbr>';
				} else {
					$breakpoint = '<abbr title="Rollback can be performed on migration" style="color:green;border:none;">No</abbr>';
				}
				$rowStyle = $migration->isCommitted() ? '' : 'background:#' . ($key % 2 == 0 ? 'ffe8e8' : 'ffe5e5') . ';';
				$nameStyle = $migration->isCommitted() ? '' : 'font-weight:bold;';
				$fileStyle = $migration->isCommitted() ? 'text-decoration:line-through;' : '';

				$html .= <<<HTML
<tr style="$rowStyle">
	<td style="{$nameStyle}white-space:nowrap;">{$migration::getName()}</td>
	<td style="text-align:center;font-weight:bold;">$breakpoint</td>
	<td style="{$fileStyle}">
		<code title="{$committedDate}">
			<a href="$editorLink">{$migration->getFileName()}</a>
		</code>
	</td>
</tr>
HTML;
			}

			$html .= <<<HTML
</tbody>
</table>
</div>
HTML;
		}

		return $html;
	}

	private function getSettingsTable(): string
	{
		$config = $this->kernel->getConfig();
		$migrationsDir = $config->migrationsDir;
		$migrationsDatabase = $config->getConnection()->getConfig('database');
		$migrationsTable = $config->getTable()->getName();

		return <<<HTML
<br>
<strong>Migrations quick overview</strong>
<table style="width:100%;">
<thead>
<tr>
	<th>Setting</th>
	<th style="width:100%;">Value</th>
</tr>
</thead>
<tbody>
<tr>
	<td>Directory</td>
	<td><code>$migrationsDir</code></td>
</tr>
<tr>
	<td>Database</td>
	<td><code>$migrationsDatabase</code></td>
</tr>
<tr>
	<td>Table</td>
	<td><code>$migrationsTable</code></td>
</tr>
</tbody>
</table>
HTML;
	}
}