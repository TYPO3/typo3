<?php

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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Core\IO\CsvStreamFilter;

/**
 * Class with helper functions for CSV handling
 */
class CsvUtility
{
    /**
     * whether to passthrough data as is, without any modification
     */
    public const TYPE_PASSTHROUGH = 0;

    /**
     * whether to remove control characters like `=`, `+`, ...
     */
    public const TYPE_REMOVE_CONTROLS = 1;

    /**
     * whether to prefix control characters like `=`, `+`, ...
     * to become `'=`, `'+`, ...
     */
    public const TYPE_PREFIX_CONTROLS = 2;

    /**
     * Convert a string, formatted as CSV, into a multidimensional array
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
                $cells = is_array($cells) ? $cells : [];
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

    /**
     * Takes a row and returns a CSV string of the values with $delim (default is ,) and $quote (default is ") as separator chars.
     *
     * @param string[] $row Input array of values
     * @param string $delim Delimited, default is comma
     * @param string $quote Quote-character to wrap around the values.
     * @param int $type Output behaviour concerning potentially harmful control literals
     * @return string A single line of CSV
     */
    public static function csvValues(array $row, string $delim = ',', string $quote = '"', int $type = self::TYPE_REMOVE_CONTROLS)
    {
        $resource = fopen('php://temp', 'w');
        if (!is_resource($resource)) {
            throw new \RuntimeException('Cannot open temporary data stream for writing', 1625556521);
        }
        $modifier = CsvStreamFilter::applyStreamFilter($resource, false);
        array_map([self::class, 'assertCellValueType'], $row);
        if ($type === self::TYPE_REMOVE_CONTROLS) {
            $row = array_map([self::class, 'removeControlLiterals'], $row);
        } elseif ($type === self::TYPE_PREFIX_CONTROLS) {
            $row = array_map([self::class, 'prefixControlLiterals'], $row);
        }
        fputcsv($resource, $modifier($row), $delim, $quote);
        fseek($resource, 0);
        $content = stream_get_contents($resource);
        return $content;
    }

    /**
     * Prefixes control literals at the beginning of a cell value with a single quote
     * (e.g. `=+value` --> `'=+value`)
     *
     * @param mixed $cellValue
     * @return bool|int|float|string|null
     */
    protected static function prefixControlLiterals($cellValue)
    {
        if (!self::shallFilterValue($cellValue)) {
            return $cellValue;
        }
        $cellValue = (string)$cellValue;
        return preg_replace('#^([\t\v=+*%/@-])#', '\'${1}', $cellValue);
    }

    /**
     * Removes control literals from the beginning of a cell value
     * (e.g. `=+value` --> `value`)
     *
     * @param mixed $cellValue
     * @return bool|int|float|string|null
     */
    protected static function removeControlLiterals($cellValue)
    {
        if (!self::shallFilterValue($cellValue)) {
            return $cellValue;
        }
        $cellValue = (string)$cellValue;
        return preg_replace('#^([\t\v=+*%/@-]+)+#', '', $cellValue);
    }

    /**
     * Asserts scalar or null types for given cell value.
     *
     * @param mixed $cellValue
     */
    protected static function assertCellValueType($cellValue): void
    {
        // int, float, string, bool, null
        if ($cellValue === null || is_scalar($cellValue)) {
            return;
        }
        throw new \RuntimeException(
            sprintf('Unexpected type %s for cell value', gettype($cellValue)),
            1625562833
        );
    }

    /**
     * Whether cell value shall be filtered, applies to everything
     * that is not or cannot be represented as boolean, integer or float.
     *
     * @param mixed $cellValue
     * @return bool
     */
    protected static function shallFilterValue($cellValue): bool
    {
        return $cellValue !== null
            && !is_bool($cellValue)
            && !is_numeric($cellValue)
            && !MathUtility::canBeInterpretedAsInteger($cellValue)
            && !MathUtility::canBeInterpretedAsFloat($cellValue);
    }
}
