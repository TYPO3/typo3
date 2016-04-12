<?php
declare (strict_types = 1);
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
                list($fieldName, $order) = GeneralUtility::trimExplode(' ', $expression, true);

                return [$fieldName, $order];
            },
            $orderExpressions
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
}
