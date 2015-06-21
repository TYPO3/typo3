<?php
namespace TYPO3\CMS\Core\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class with helper functions for CSV handling
 */
class CsvUtility {

	/**
	 * Convert a string, formatted as CSV, into an multidimensional array
	 *
	 * @param string $input The CSV input
	 * @param string $fieldDelimiter The field delimiter
	 * @param string $fieldEnclosure The field enclosure
	 * @param string $rowDelimiter The row delimiter
	 * @param int $maximumColumns The maximum amount of columns
	 * @return array
	 */
	static public function csvToArray($input, $fieldDelimiter = ',', $fieldEnclosure = '"', $rowDelimiter = LF, $maximumColumns = 0) {
		$multiArray = array();
		$maximumCellCount = 0;

		// explode() would not work with enclosed newlines
		$rows = str_getcsv($input, $rowDelimiter);

		foreach ($rows as $row) {
			$cells = str_getcsv($row, $fieldDelimiter, $fieldEnclosure);

			$maximumCellCount = max(count($cells), $maximumCellCount);

			$multiArray[] = $cells;
		}

		if ($maximumColumns > $maximumCellCount) {
			$maximumCellCount = $maximumColumns;
		}

		foreach ($multiArray as &$row) {
			for ($key = 0; $key < $maximumCellCount; $key++) {
				if (
					$maximumColumns > 0
					&& $maximumColumns < $maximumCellCount
					&& $key >= $maximumColumns
				) {
					if (isset($row[$key])) {
						unset($row[$key]);
					}
				} elseif (!isset($row[$key])) {
					$row[$key] = '';
				}
			}
		}

		return $multiArray;
	}
}