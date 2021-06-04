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

namespace TYPO3\CMS\IndexedSearch\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class with common methods used across various classes in the indexed search.
 * Implementation is provided by various people from the TYPO3 community.
 * @internal
 */
class IndexedSearchUtility
{
    /**
     * Check if the tables provided are configured for usage. This becomes
     * necessary for extensions that provide additional database functionality
     * like indexed_search_mysql.
     *
     * @param string $tableName Table name to check
     * @return bool True if the given table is used
     */
    public static function isTableUsed($tableName)
    {
        $tableList = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['use_tables'];
        return GeneralUtility::inList($tableList, $tableName);
    }

    /**
     * md5 integer hash
     * Using 7 instead of 8 just because that makes the integers lower than 32 bit (28 bit) and so they do not interfere with UNSIGNED integers or PHP-versions which has varying output from the hexdec function.
     *
     * @param string $stringToHash String to hash
     * @return int Integer interpretation of the md5 hash of input string.
     */
    public static function md5inthash($stringToHash)
    {
        return (int)hexdec(substr(md5($stringToHash), 0, 7));
    }

    /**
     * Takes a search-string (WITHOUT SLASHES or else it'll be a little spooky , NOW REMEMBER to unslash!!)
     * Sets up search words with operators.
     *
     * @param string $sword The input search-word string.
     * @param string $defaultOperator
     * @param array $operatorTranslateTable
     * @return array
     */
    public static function getExplodedSearchString($sword, $defaultOperator, $operatorTranslateTable)
    {
        $swordArray = [];
        $sword = trim($sword);
        if ($sword) {
            $components = self::split($sword);
            if (is_array($components)) {
                $i = 0;
                $lastoper = '';
                foreach ($components as $key => $val) {
                    $operator = self::getOperator($val, $operatorTranslateTable);
                    if ($operator) {
                        $lastoper = $operator;
                    } elseif (strlen($val) > 1) {
                        // A searchword MUST be at least two characters long!
                        $swordArray[$i]['sword'] = $val;
                        $swordArray[$i]['oper'] = $lastoper ?: $defaultOperator;
                        $lastoper = '';
                        $i++;
                    }
                }
            }
        }
        return $swordArray;
    }

    /**
     * Used to split a search-word line up into elements to search for. This function will detect boolean words like AND and OR, + and -, and even find sentences encapsulated in ""
     * This function could be re-written to be more clean and effective - yet it's not that important.
     *
     * @param string $origSword The raw sword string from outside
     * @param string $specchars Special chars which are used as operators (+- is default)
     * @param string $delchars Special chars which are deleted if the append the searchword (+-., is default)
     * @return mixed Returns an ARRAY if there were search words, otherwise the return value may be unset.
     */
    protected static function split($origSword, $specchars = '+-', $delchars = '+.,-')
    {
        $value = null;
        $sword = $origSword;
        $specs = '[' . preg_quote($specchars, '/') . ']';
        // As long as $sword is TRUE (that means $sword MUST be reduced little by little until its empty inside the loop!)
        while ($sword) {
            // There was a double-quote and we will then look for the ending quote.
            if (preg_match('/^"/', $sword)) {
                // Removes first double-quote
                $sword = (string)preg_replace('/^"/', '', $sword);
                // Removes everything till next double-quote
                preg_match('/^[^"]*/', $sword, $reg);
                // reg[0] is the value, should not be trimmed
                $value[] = $reg[0];
                $sword = (string)preg_replace('/^' . preg_quote($reg[0], '/') . '/', '', $sword);
                // Removes last double-quote
                $sword = trim((string)preg_replace('/^"/', '', $sword));
            } elseif (preg_match('/^' . $specs . '/', $sword, $reg)) {
                $value[] = $reg[0];
                // Removes = sign
                $sword = trim((string)preg_replace('/^' . $specs . '/', '', $sword));
            } elseif (preg_match('/[\\+\\-]/', $sword)) {
                // Check if $sword contains + or -
                // + and - shall only be interpreted as $specchars when there's whitespace before it
                // otherwise it's included in the searchword (e.g. "know-how")
                // explode $sword to single words
                $a_sword = explode(' ', $sword);
                // get first word
                $word = (string)array_shift($a_sword);
                // Delete $delchars at end of string
                $word = rtrim($word, $delchars);
                // add searchword to values
                $value[] = $word;
                // re-build $sword
                $sword = implode(' ', $a_sword);
            } else {
                // There are no double-quotes around the value. Looking for next (space) or special char.
                preg_match('/^[^ ' . preg_quote($specchars, '/') . ']*/', $sword, $reg);
                // Delete $delchars at end of string
                $word = rtrim(trim($reg[0]), $delchars);
                $value[] = $word;
                $sword = trim((string)preg_replace('/^' . preg_quote($reg[0], '/') . '/', '', $sword));
            }
        }
        return $value;
    }

    /**
     * This returns an SQL search-operator (eg. AND, OR, NOT) translated from the current localized set of operators (eg. in danish OG, ELLER, IKKE).
     *
     * @param string $operator The possible operator to find in the internal operator array.
     * @param array $operatorTranslateTable an array of possible operators
     * @return string|null If found, the SQL operator for the localized input operator.
     */
    protected static function getOperator($operator, $operatorTranslateTable)
    {
        $operator = trim($operator);
        // case-conversion is charset insensitive, but it doesn't spoil
        // anything if input string AND operator table is already converted
        $operator = strtolower($operator);
        foreach ($operatorTranslateTable as $key => $val) {
            $item = $operatorTranslateTable[$key][0];
            // See note above.
            $item = strtolower($item);
            if ($operator == $item) {
                return $operatorTranslateTable[$key][1];
            }
        }

        return null;
    }

    /**
     * Gets the unixtime as milliseconds.
     *
     * @return int The unixtime as milliseconds
     */
    public static function milliseconds()
    {
        return (int)round(microtime(true) * 1000);
    }
}
