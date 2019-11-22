<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains misc helper methods to build syntactically valid SQL queries.
 * Most helper functions are required to deal with legacy data where the
 * format of the input is not strict enough to reliably use the SQL parts
 * in queries directly.
 *
 * @internal
 */
class QueryHelper
{
    /**
     * Takes an input, possibly prefixed with ORDER BY, and explodes it into
     * and array of arrays where each item consists of a fieldName and a order
     * direction.
     *
     * Each of the resulting fieldName/direction pairs can be used passed into
     * QueryBuilder::orderBy() so sort a query result set.
     *
     * @param string $input eg . "ORDER BY title, uid
     * @return array|array[] Array of arrays containing fieldName/direction pairs
     */
    public static function parseOrderBy(string $input): array
    {
        $input = preg_replace('/^(?:ORDER[[:space:]]*BY[[:space:]]*)+/i', '', trim($input)) ?: '';
        $orderExpressions = GeneralUtility::trimExplode(',', $input, true);

        return array_map(
            function ($expression) {
                $fieldNameOrderArray = GeneralUtility::trimExplode(' ', $expression, true);
                $fieldName = $fieldNameOrderArray[0] ?? null;
                $order = $fieldNameOrderArray[1] ?? null;

                return [$fieldName, $order];
            },
            $orderExpressions
        );
    }

    /**
     * Takes an input, possibly prefixed with FROM, and explodes it into
     * and array of arrays where each item consists of a tableName and an
     * optional alias name.
     *
     * Each of the resulting pairs can be used with QueryBuilder::from()
     * to select from one or more tables.
     *
     * @param string $input eg . "FROM aTable, anotherTable AS b, aThirdTable c"
     * @return array|array[] Array of arrays containing tableName/alias pairs
     */
    public static function parseTableList(string $input): array
    {
        $input = preg_replace('/^(?:FROM[[:space:]]+)+/i', '', trim($input)) ?: '';
        $tableExpressions = GeneralUtility::trimExplode(',', $input, true);

        return array_map(
            function ($expression) {
                [$tableName, $as, $alias] = array_pad(GeneralUtility::trimExplode(' ', $expression, true), 3, null);

                if (!empty($as) && strtolower($as) === 'as' && !empty($alias)) {
                    return [$tableName, $alias];
                }
                if (!empty($as) && empty($alias)) {
                    return [$tableName, $as];
                }
                return [$tableName, null];
            },
            $tableExpressions
        );
    }

    /**
     * Removes the prefix "GROUP BY" from the input string.
     *
     * This function should be used when you can't guarantee that the string
     * that you want to use as a GROUP BY fragment is not prefixed.
     *
     * @param string $input eg. "GROUP BY title, uid
     * @return array|string[] column names to group by
     */
    public static function parseGroupBy(string $input): array
    {
        $input = preg_replace('/^(?:GROUP[[:space:]]*BY[[:space:]]*)+/i', '', trim($input)) ?: '';

        return GeneralUtility::trimExplode(',', $input, true);
    }

    /**
     * Split a JOIN SQL fragment into table name, alias and join conditions.
     *
     * @param string $input eg. "JOIN tableName AS a ON a.uid = anotherTable.uid_foreign"
     * @return array assoc array consisting of the keys tableName, tableAlias and joinCondition
     */
    public static function parseJoin(string $input): array
    {
        $input = trim($input);
        $quoteCharacter = ' ';
        // Check if the tableName is quoted
        if ($input[0] === '`' || $input[0] === '"') {
            $quoteCharacter .= $input[0];
            $input = substr($input, 1);
            $tableName = strtok($input, $quoteCharacter);
        } else {
            $tableName = strtok($input, $quoteCharacter);
        }

        $tableAlias = strtok($quoteCharacter);
        if (strtolower($tableAlias) === 'as') {
            $tableAlias = strtok($quoteCharacter);
            // Skip the next token which must be ON
            strtok(' ');
            $joinCondition = strtok('');
        } elseif (strtolower($tableAlias) === 'on') {
            $tableAlias = null;
            $joinCondition = strtok('');
        } else {
            // Skip the next token which must be ON
            strtok(' ');
            $joinCondition = strtok('');
        }

        // Catch the edge case that the table name is unquoted and the
        // table alias is actually quoted. This will not work in the case
        // that the quoted table alias contains whitespace.
        $firstCharacterOfTableAlias = $tableAlias[0] ?? null;
        if ($firstCharacterOfTableAlias === '`' || $firstCharacterOfTableAlias === '"') {
            $tableAlias = substr($tableAlias, 1, -1);
        }

        $tableAlias = $tableAlias ?: $tableName;

        return ['tableName' => $tableName, 'tableAlias' => $tableAlias, 'joinCondition' => $joinCondition];
    }

    /**
     * Removes the prefixes AND/OR from the input string.
     *
     * This function should be used when you can't guarantee that the string
     * that you want to use as a WHERE fragment is not prefixed.
     *
     * @param string $constraint The where part fragment with a possible leading AND or OR operator
     * @return string The modified where part without leading operator
     */
    public static function stripLogicalOperatorPrefix(string $constraint): string
    {
        return preg_replace('/^(?:(AND|OR)[[:space:]]*)+/i', '', trim($constraint)) ?: '';
    }

    /**
     * Returns the date and time formats compatible with the given database.
     *
     * This simple method should probably be deprecated and removed later.
     *
     * @return array
     */
    public static function getDateTimeFormats()
    {
        return [
            'date' => [
                'empty' => '0000-00-00',
                'format' => 'Y-m-d'
            ],
            'datetime' => [
                'empty' => '0000-00-00 00:00:00',
                'format' => 'Y-m-d H:i:s'
            ],
            'time' => [
                'empty' => '00:00:00',
                'format' => 'H:i:s'
            ]
        ];
    }

    /**
     * Returns the date and time types compatible with the given database.
     *
     * This simple method should probably be deprecated and removed later.
     *
     * @return array
     */
    public static function getDateTimeTypes()
    {
        return [
            'date',
            'datetime',
            'time'
        ];
    }

    /**
     * Quote database table/column names indicated by {#identifier} markup in a SQL fragment string.
     * This is an intermediate step to make SQL fragments in Typoscript and TCA database agnostic.
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param string $sql
     * @return string
     */
    public static function quoteDatabaseIdentifiers(Connection $connection, string $sql): string
    {
        if (strpos($sql, '{#') !== false) {
            $sql = preg_replace_callback(
                '/{#(?P<identifier>[^}]+)}/',
                function (array $matches) use ($connection) {
                    return $connection->quoteIdentifier($matches['identifier']);
                },
                $sql
            );
        }

        return $sql;
    }
}
