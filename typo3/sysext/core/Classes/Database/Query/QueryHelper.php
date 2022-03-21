<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Database\Query;

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
     * and array of arrays where each item consists of a fieldName and an order
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
            static function ($expression) {
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
            static function ($expression) {
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
        $matchQuotingStartCharacters = [
            '`' => '`',
            '"' => '"',
            '[' => '[]',
        ];

        // Check if the tableName is quoted
        if ($matchQuotingStartCharacters[$input[0]] ?? false) {
            $quoteCharacter .= $matchQuotingStartCharacters[$input[0]];
            $input = substr($input, 1);
            $tableName = strtok($input, $quoteCharacter);
        } else {
            $tableName = strtok($input, $quoteCharacter);
        }

        $tableAlias = (string)strtok($quoteCharacter);
        if (strtolower($tableAlias) === 'as') {
            $tableAlias = (string)strtok($quoteCharacter);
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
        if ($matchQuotingStartCharacters[$firstCharacterOfTableAlias] ?? false) {
            $tableAlias = substr((string)$tableAlias, 1, -1);
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
                'format' => 'Y-m-d',
                'reset' => null,
            ],
            'datetime' => [
                'empty' => '0000-00-00 00:00:00',
                'format' => 'Y-m-d H:i:s',
                'reset' => null,
            ],
            'time' => [
                'empty' => '00:00:00',
                'format' => 'H:i:s',
                'reset' => 0,
            ],
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
            'time',
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
        if (str_contains($sql, '{#')) {
            $sql = preg_replace_callback(
                '/{#(?P<identifier>[^}]+)}/',
                static function (array $matches) use ($connection) {
                    return $connection->quoteIdentifier($matches['identifier']);
                },
                $sql
            );
        }

        return $sql;
    }

    /**
     * Implode array to comma separated list with database int-quoted values to be used as direct
     * value list for database 'in(...)' or  'notIn(...') expressions. Empty array will return 'NULL'
     * as string to avoid database query failure, as 'in()' is invalid, but 'in(null)' will be executed.
     *
     * This function should be used with care, as it should be preferred to use placeholders, although
     * there are use cases in some (system) areas which reaches placeholder limit really fast.
     *
     * Return value should only be used as value list for database query 'IN()' or 'NOTIN()' .
     *
     * Will be removed in v12, use QueryHelper::quoteArrayBasedValueListToIntegerList() instead.
     *
     * @param array $values
     * @param Connection $connection
     * @return string
     */
    public static function implodeToIntQuotedValueList(array $values, Connection $connection): string
    {
        if (empty($values)) {
            return 'NULL';
        }

        // Ensure values are all integer
        $values = GeneralUtility::intExplode(',', implode(',', $values));

        // Ensure all values are quoted as int for used dbms
        array_walk($values, static function (&$value) use ($connection) {
            $value = $connection->quote($value, Connection::PARAM_INT);
        });

        return implode(',', $values);
    }

    /**
     * Implode array to comma separated list with database string-quoted values to be used as direct
     * value list for database 'in(...)' or  'notIn(...') expressions. Empty array will return 'NULL'
     * as string to avoid database query failure, as 'in()' is invalid, but 'in(null)' will be executed.
     *
     * This function should be used with care, as it should be preferred to use placeholders, although
     * there are use cases in some (system) areas which reaches placeholder limit really fast.
     *
     * Return value should only be used as value list for database query 'IN()' or 'NOTIN()' .
     *
     * Will be removed in v12, use QueryHelper::quoteArrayBasedValueListToStringList() instead.
     *
     * @param array $values
     * @param Connection $connection
     * @return string
     */
    public static function implodeToStringQuotedValueList(array $values, Connection $connection): string
    {
        if (empty($values)) {
            return 'NULL';
        }

        // Ensure values are all strings
        $values = GeneralUtility::trimExplode(',', implode(',', $values));

        // Ensure all values are quoted as string values for used dbmns
        array_walk($values, static function (&$value) use ($connection) {
            $value = $connection->quote($value, Connection::PARAM_STR);
        });

        return implode(',', $values);
    }
}
