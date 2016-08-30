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
class CsvUtility
{
    /**
     * Convert a string, formatted as CSV, into an multidimensional array
     *
     * This cannot be done by str_getcsv, since it's impossible to handle enclosed cells with a line feed in it
     *
     * @param string $input The CSV input
     * @param string $fieldDelimiter The field delimiter
     * @param string $fieldEnclosure The field enclosure
     * @param int $maximumColumns The maximum amount of columns
     * @return array
     */
    public static function csvToArray($input, $fieldDelimiter = ',', $fieldEnclosure = '"', $maximumColumns = 0)
    {
        $multiArray = [];
        $maximumCellCount = 0;

        if (($handle = fopen('php://memory', 'r+')) !== false) {
            fwrite($handle, $input);
            rewind($handle);
            while (($cells = fgetcsv($handle, 0, $fieldDelimiter, $fieldEnclosure)) !== false) {
                $maximumCellCount = max(count($cells), $maximumCellCount);
                $multiArray[] = preg_replace('|<br */?>|i', LF, $cells);
            }
            fclose($handle);
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
