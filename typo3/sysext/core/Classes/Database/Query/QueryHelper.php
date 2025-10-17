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
        if ($input === '') {
            return [];
        }
        $input = preg_replace('/^(?:ORDER[[:space:]]*BY[[:space:]]*)+/i', '', trim($input)) ?: '';
        $orderExpressions = GeneralUtility::trimExplode(',', $input, true);

        return array_map(
            static function (string $expression): array {
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
        if ($input === '') {
            return [];
        }
        $input = preg_replace('/^(?:FROM[[:space:]]+)+/i', '', trim($input)) ?: '';
        $tableExpressions = GeneralUtility::trimExplode(',', $input, true);

        return array_map(
            static function (string $expression): array {
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
        if ($input === '') {
            return [];
        }
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
        $firstCharOfInputValue = $input[0] ?? '';
        if ($matchQuotingStartCharacters[$firstCharOfInputValue] ?? false) {
            $quoteCharacter .= $matchQuotingStartCharacters[$firstCharOfInputValue];
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
        $firstCharacterOfTableAlias = $tableAlias[0] ?? '';
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
     * This simple method should probably be deprecated and removed later.
     */
    public static function getDateTimeFormats(): array
    {
        return [
            'date' => [
                'empty' => '0000-00-00',
                'format' => 'Y-m-d',
            ],
            'datetime' => [
                'empty' => '0000-00-00 00:00:00',
                'format' => 'Y-m-d H:i:s',
            ],
            'time' => [
                'empty' => '00:00:00',
                'format' => 'H:i:s',
            ],
        ];
    }

    /**
     * Returns the date and time types compatible with the given database.
     * This simple method should probably be deprecated and removed later.
     */
    public static function getDateTimeTypes(): array
    {
        return [
            'date',
            'datetime',
            'time',
        ];
    }

    public static function transformDateTimeToDatabaseValue(
        ?\DateTimeInterface $datetime,
        bool $isNullable,
        string $format,
        ?string $persistenceType,
    ): int|string|null {
        if ($datetime === null) {
            if ($isNullable) {
                return null;
            }
            if ($persistenceType === null) {
                return 0;
            }
            return self::getDateTimeFormats()[$persistenceType]['empty'] ?? null;
        }

        if (!$datetime instanceof \DateTimeImmutable) {
            $datetime = \DateTimeImmutable::createFromInterface($datetime);
        }

        // Apply format-specific normalizations
        if ($format === 'time') {
            // time(sec) is stored as elapsed seconds in DB, hence we base the time on 1970-01-01
            $datetime = $datetime->setDate(1970, 01, 01)->setTime((int)$datetime->format('H'), (int)$datetime->format('i'), 0);
        } elseif ($format === 'timesec' || $persistenceType === 'time') {
            $datetime = $datetime->setDate(1970, 01, 01);
        } elseif ($format === 'date' || $persistenceType === 'date') {
            $datetime = $datetime->setTime(0, 0, 0);
        }

        // Native DATETIME, DATE or TIME field
        if (in_array($persistenceType, self::getDateTimeTypes(), true)) {
            $dateTimeFormats = self::getDateTimeFormats();
            $persistenceFormat = $dateTimeFormats[$persistenceType]['format'];
            if ($persistenceType === 'datetime') {
                // native DATETIME values are stored in server LOCALTIME. Force conversion to the servers current timezone.
                $datetime = $datetime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            return $datetime->format($persistenceFormat);
        }

        // Time is stored in seconds for integer fields
        if ($format === 'timesec' || $format === 'time') {
            return (int)$datetime->format('H') * 3600 + (int)$datetime->format('i') * 60 + (int)$datetime->format('s');
        }

        // Encode as unix timestamp (int) if no native field is used
        return $datetime->getTimestamp();
    }

    /**
     * Quote database table/column names indicated by {#identifier} markup in a SQL fragment string.
     * This is an intermediate step to make SQL fragments in Typoscript and TCA database agnostic.
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
}
