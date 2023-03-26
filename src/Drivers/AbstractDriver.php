<?php

namespace Semisedlak\Migratte\Drivers;

use Dibi\Row;

class AbstractDriver
{
	/**
	 * @param string[] $newColumns
	 * @param Row[]    $columnsResult
	 * @return string[]
	 */
	protected function getColumnsToAdd(array $newColumns = [], array $columnsResult = []): array
	{
		if (empty($newColumns) || empty($columnsResult)) {
			return [];
		}

		$existingColumns = [];
		foreach ($columnsResult as $column) {
			$existingColumns[] = $column['name'];
		}

		$columnsToAdd = [];
		foreach ($newColumns as $columnName => $columnType) {
			if (!in_array($columnName, $existingColumns)) {
				$columnsToAdd[$columnName] = $columnType;
			}
		}

		return $columnsToAdd;
	}
}
