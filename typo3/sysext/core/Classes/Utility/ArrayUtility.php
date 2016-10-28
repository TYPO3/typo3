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
 * Class with helper functions for array handling
 */
class ArrayUtility
{
    /**
     * Reduce an array by a search value and keep the array structure.
     *
     * Comparison is type strict:
     * - For a given needle of type string, integer, array or boolean,
     * value and value type must match to occur in result array
     * - For a given object, an object within the array must be a reference to
     * the same object to match (not just different instance of same class)
     *
     * Example:
     * - Needle: 'findMe'
     * - Given array:
     * array(
     *   'foo' => 'noMatch',
     *   'bar' => 'findMe',
     *   'foobar => array(
     *     'foo' => 'findMe',
     *   ),
     * );
     * - Result:
     * array(
     *   'bar' => 'findMe',
     *   'foobar' => array(
     *     'foo' => findMe',
     *   ),
     * );
     *
     * See the unit tests for more examples and expected behaviour
     *
     * @param mixed $needle The value to search for
     * @param array $haystack The array in which to search
     * @return array $haystack array reduced matching $needle values
     */
    public static function filterByValueRecursive($needle = '', array $haystack = [])
    {
        $resultArray = [];
        // Define a lambda function to be applied to all members of this array dimension
        // Call recursive if current value is of type array
        // Write to $resultArray (by reference!) if types and value match
        $callback = function (&$value, $key) use ($needle, &$resultArray) {
            if ($value === $needle) {
                ($resultArray[$key] = $value);
            } elseif (is_array($value)) {
                ($subArrayMatches = static::filterByValueRecursive($needle, $value));
                if (!empty($subArrayMatches)) {
                    ($resultArray[$key] = $subArrayMatches);
                }
            }
        };
        // array_walk() is not affected by the internal pointers, no need to reset
        array_walk($haystack, $callback);
        // Pointers to result array are reset internally
        return $resultArray;
    }

    /**
     * Checks if a given path exists in array
     *
     * Example:
     * - array:
     * array(
     *   'foo' => array(
     *     'bar' = 'test',
     *   )
     * );
     * - path: 'foo/bar'
     * - return: TRUE
     *
     * @param array $array Given array
     * @param string $path Path to test, 'foo/bar/foobar'
     * @param string $delimiter Delimiter for path, default /
     * @return bool TRUE if path exists in array
     */
    public static function isValidPath(array $array, $path, $delimiter = '/')
    {
        $isValid = true;
        try {
            // Use late static binding to enable mocking of this call in unit tests
            static::getValueByPath($array, $path, $delimiter);
        } catch (\RuntimeException $e) {
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * Returns a value by given path
     *
     * Example
     * - array:
     * array(
     *   'foo' => array(
     *     'bar' => array(
     *       'baz' => 42
     *     )
     *   )
     * );
     * - path: foo/bar/baz
     * - return: 42
     *
     * If a path segments contains a delimiter character, the path segment
     * must be enclosed by " (double quote), see unit tests for details
     *
     * @param array $array Input array
     * @param string $path Path within the array
     * @param string $delimiter Defined path delimiter, default /
     * @return mixed
     * @throws \RuntimeException
     */
    public static function getValueByPath(array $array, $path, $delimiter = '/')
    {
        if (!is_string($path)) {
            throw new \RuntimeException('Path must be a string', 1477699595);
        }
        if ($path === '') {
            throw new \RuntimeException('Path must not be empty', 1341397767);
        }
        // Extract parts of the path
        $path = str_getcsv($path, $delimiter);
        // Loop through each part and extract its value
        $value = $array;
        foreach ($path as $segment) {
            if (array_key_exists($segment, $value)) {
                // Replace current value with child
                $value = $value[$segment];
            } else {
                // Fail if key does not exist
                throw new \RuntimeException('Path does not exist in array', 1341397869);
            }
        }
        return $value;
    }

    /**
     * Modifies or sets a new value in an array by given path
     *
     * Example:
     * - array:
     * array(
     *   'foo' => array(
     *     'bar' => 42,
     *   ),
     * );
     * - path: foo/bar
     * - value: 23
     * - return:
     * array(
     *   'foo' => array(
     *     'bar' => 23,
     *   ),
     * );
     *
     * @param array $array Input array to manipulate
     * @param string $path Path in array to search for
     * @param mixed $value Value to set at path location in array
     * @param string $delimiter Path delimiter
     * @return array Modified array
     * @throws \RuntimeException
     */
    public static function setValueByPath(array $array, $path, $value, $delimiter = '/')
    {
        if (!is_string($path)) {
            throw new \RuntimeException('Path must be a string', 1341406402);
        }
        if ($path === '') {
            throw new \RuntimeException('Path must not be empty', 1341406194);
        }
        // Extract parts of the path
        $path = str_getcsv($path, $delimiter);
        // Point to the root of the array
        $pointer = &$array;
        // Find path in given array
        foreach ($path as $segment) {
            // Fail if the part is empty
            if ($segment === '') {
                throw new \RuntimeException('Invalid path segment specified', 1341406846);
            }
            // Create cell if it doesn't exist
            if (!array_key_exists($segment, $pointer)) {
                $pointer[$segment] = [];
            }
            // Set pointer to new cell
            $pointer = &$pointer[$segment];
        }
        // Set value of target cell
        $pointer = $value;
        return $array;
    }

    /**
     * Remove a sub part from an array specified by path
     *
     * @param array $array Input array to manipulate
     * @param string $path Path to remove from array
     * @param string $delimiter Path delimiter
     * @return array Modified array
     * @throws \RuntimeException
     */
    public static function removeByPath(array $array, $path, $delimiter = '/')
    {
        if (!is_string($path)) {
            throw new \RuntimeException('Path must be a string', 1371757719);
        }
        if ($path === '') {
            throw new \RuntimeException('Path must not be empty', 1371757718);
        }
        // Extract parts of the path
        $path = str_getcsv($path, $delimiter);
        $pathDepth = count($path);
        $currentDepth = 0;
        $pointer = &$array;
        // Find path in given array
        foreach ($path as $segment) {
            $currentDepth++;
            // Fail if the part is empty
            if ($segment === '') {
                throw new \RuntimeException('Invalid path segment specified', 1371757720);
            }
            if (!array_key_exists($segment, $pointer)) {
                throw new \RuntimeException('Path segment ' . $segment . ' does not exist in array', 1371758436);
            }
            if ($currentDepth === $pathDepth) {
                unset($pointer[$segment]);
            } else {
                $pointer = &$pointer[$segment];
            }
        }
        return $array;
    }

    /**
     * Sorts an array recursively by key
     *
     * @param array $array Array to sort recursively by key
     * @return array Sorted array
     */
    public static function sortByKeyRecursive(array $array)
    {
        ksort($array);
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $array[$key] = self::sortByKeyRecursive($value);
            }
        }
        return $array;
    }

    /**
     * Sort an array of arrays by a given key using uasort
     *
     * @param array $arrays Array of arrays to sort
     * @param string $key Key to sort after
     * @param bool $ascending Set to TRUE for ascending order, FALSE for descending order
     * @return array Array of sorted arrays
     * @throws \RuntimeException
     */
    public static function sortArraysByKey(array $arrays, $key, $ascending = true)
    {
        if (empty($arrays)) {
            return $arrays;
        }
        $sortResult = uasort($arrays, function (array $a, array $b) use ($key, $ascending) {
            if (!isset($a[$key]) || !isset($b[$key])) {
                throw new \RuntimeException('The specified sorting key "' . $key . '" is not available in the given array.', 1373727309);
            }
            return ($ascending) ? strcasecmp($a[$key], $b[$key]) : strcasecmp($b[$key], $a[$key]);
        });
        if (!$sortResult) {
            throw new \RuntimeException('The function uasort() failed for unknown reasons.', 1373727329);
        }
        return $arrays;
    }

    /**
     * Exports an array as string.
     * Similar to var_export(), but representation follows the PSR-2 and TYPO3 core CGL.
     *
     * See unit tests for detailed examples
     *
     * @param array $array Array to export
     * @param int $level Internal level used for recursion, do *not* set from outside!
     * @return string String representation of array
     * @throws \RuntimeException
     */
    public static function arrayExport(array $array = [], $level = 0)
    {
        $lines = '[' . LF;
        $level++;
        $writeKeyIndex = false;
        $expectedKeyIndex = 0;
        foreach ($array as $key => $value) {
            if ($key === $expectedKeyIndex) {
                $expectedKeyIndex++;
            } else {
                // Found a non integer or non consecutive key, so we can break here
                $writeKeyIndex = true;
                break;
            }
        }
        foreach ($array as $key => $value) {
            // Indention
            $lines .= str_repeat('    ', $level);
            if ($writeKeyIndex) {
                // Numeric / string keys
                $lines .= is_int($key) ? $key . ' => ' : '\'' . $key . '\' => ';
            }
            if (is_array($value)) {
                if (!empty($value)) {
                    $lines .= self::arrayExport($value, $level);
                } else {
                    $lines .= '[],' . LF;
                }
            } elseif (is_int($value) || is_float($value)) {
                $lines .= $value . ',' . LF;
            } elseif (is_null($value)) {
                $lines .= 'null' . ',' . LF;
            } elseif (is_bool($value)) {
                $lines .= $value ? 'true' : 'false';
                $lines .= ',' . LF;
            } elseif (is_string($value)) {
                // Quote \ to \\
                $stringContent = str_replace('\\', '\\\\', $value);
                // Quote ' to \'
                $stringContent = str_replace('\'', '\\\'', $stringContent);
                $lines .= '\'' . $stringContent . '\'' . ',' . LF;
            } else {
                throw new \RuntimeException('Objects are not supported', 1342294987);
            }
        }
        $lines .= str_repeat('    ', ($level - 1)) . ']' . ($level - 1 == 0 ? '' : ',' . LF);
        return $lines;
    }

    /**
     * Converts a multidimensional array to a flat representation.
     *
     * See unit tests for more details
     *
     * Example:
     * - array:
     * array(
     *   'first.' => array(
     *     'second' => 1
     *   )
     * )
     * - result:
     * array(
     *   'first.second' => 1
     * )
     *
     * Example:
     * - array:
     * array(
     *   'first' => array(
     *     'second' => 1
     *   )
     * )
     * - result:
     * array(
     *   'first.second' => 1
     * )
     *
     * @param array $array The (relative) array to be converted
     * @param string $prefix The (relative) prefix to be used (e.g. 'section.')
     * @return array
     */
    public static function flatten(array $array, $prefix = '')
    {
        $flatArray = [];
        foreach ($array as $key => $value) {
            // Ensure there is no trailling dot:
            $key = rtrim($key, '.');
            if (!is_array($value)) {
                $flatArray[$prefix . $key] = $value;
            } else {
                $flatArray = array_merge($flatArray, self::flatten($value, $prefix . $key . '.'));
            }
        }
        return $flatArray;
    }

    /**
     * Determine the intersections between two arrays, recursively comparing keys
     * A complete sub array of $source will be preserved, if the key exists in $mask.
     *
     * See unit tests for more examples and edge cases.
     *
     * Example:
     * - source:
     * array(
     *   'key1' => 'bar',
     *   'key2' => array(
     *     'subkey1' => 'sub1',
     *     'subkey2' => 'sub2',
     *   ),
     *   'key3' => 'baz',
     * )
     * - mask:
     * array(
     *   'key1' => NULL,
     *   'key2' => array(
     *     'subkey1' => exists',
     *   ),
     * )
     * - return:
     * array(
     *   'key1' => 'bar',
     *   'key2' => array(
     *     'subkey1' => 'sub1',
     *   ),
     * )
     *
     * @param array $source Source array
     * @param array $mask Array that has the keys which should be kept in the source array
     * @return array Keys which are present in both arrays with values of the source array
     */
    public static function intersectRecursive(array $source, array $mask = [])
    {
        $intersection = [];
        foreach ($source as $key => $_) {
            if (!array_key_exists($key, $mask)) {
                continue;
            }
            if (is_array($source[$key]) && is_array($mask[$key])) {
                $value = self::intersectRecursive($source[$key], $mask[$key]);
                if (!empty($value)) {
                    $intersection[$key] = $value;
                }
            } else {
                $intersection[$key] = $source[$key];
            }
        }
        return $intersection;
    }

    /**
     * Renumber the keys of an array to avoid leaps if keys are all numeric.
     *
     * Is called recursively for nested arrays.
     *
     * Example:
     *
     * Given
     *  array(0 => 'Zero' 1 => 'One', 2 => 'Two', 4 => 'Three')
     * as input, it will return
     *  array(0 => 'Zero' 1 => 'One', 2 => 'Two', 3 => 'Three')
     *
     * Will treat keys string representations of number (ie. '1') equal to the
     * numeric value (ie. 1).
     *
     * Example:
     * Given
     *  array('0' => 'Zero', '1' => 'One' )
     * it will return
     *  array(0 => 'Zero', 1 => 'One')
     *
     * @param array $array Input array
     * @param int $level Internal level used for recursion, do *not* set from outside!
     * @return array
     */
    public static function renumberKeysToAvoidLeapsIfKeysAreAllNumeric(array $array = [], $level = 0)
    {
        $level++;
        $allKeysAreNumeric = true;
        foreach ($array as $key => $_) {
            if (is_numeric($key) === false) {
                $allKeysAreNumeric = false;
                break;
            }
        }
        $renumberedArray = $array;
        if ($allKeysAreNumeric === true) {
            $renumberedArray = array_values($array);
        }
        foreach ($renumberedArray as $key => $value) {
            if (is_array($value)) {
                $renumberedArray[$key] = self::renumberKeysToAvoidLeapsIfKeysAreAllNumeric($value, $level);
            }
        }
        return $renumberedArray;
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are
     * overridden as well), overruling similar values in the original array
     * with the values of the overrule array.
     * In case of identical keys, ie. keeping the values of the overrule array.
     *
     * This method takes the original array by reference for speed optimization with large arrays
     *
     * The differences to the existing PHP function array_merge_recursive() are:
     *  * Keys of the original array can be unset via the overrule array. ($enableUnsetFeature)
     *  * Much more control over what is actually merged. ($addKeys, $includeEmptyValues)
     *  * Elements or the original array get overwritten if the same key is present in the overrule array.
     *
     * @param array $original Original array. It will be *modified* by this method and contains the result afterwards!
     * @param array $overrule Overrule array, overruling the original array
     * @param bool $addKeys If set to FALSE, keys that are NOT found in $original will not be set. Thus only existing value can/will be overruled from overrule array.
     * @param bool $includeEmptyValues If set, values from $overrule will overrule if they are empty or zero.
     * @param bool $enableUnsetFeature If set, special values "__UNSET" can be used in the overrule array in order to unset array keys in the original array.
     * @return void
     */
    public static function mergeRecursiveWithOverrule(array &$original, array $overrule, $addKeys = true, $includeEmptyValues = true, $enableUnsetFeature = true)
    {
        foreach ($overrule as $key => $_) {
            if ($enableUnsetFeature && $overrule[$key] === '__UNSET') {
                unset($original[$key]);
                continue;
            }
            if (isset($original[$key]) && is_array($original[$key])) {
                if (is_array($overrule[$key])) {
                    self::mergeRecursiveWithOverrule($original[$key], $overrule[$key], $addKeys, $includeEmptyValues, $enableUnsetFeature);
                }
            } elseif (
                ($addKeys || isset($original[$key])) &&
                ($includeEmptyValues || $overrule[$key])
            ) {
                $original[$key] = $overrule[$key];
            }
        }
        // This line is kept for backward compatibility reasons.
        reset($original);
    }

    /**
     * Check if an string item exists in an array.
     * Please note that the order of function parameters is reverse compared to the PHP function in_array()!!!
     *
     * Comparison to PHP in_array():
     * -> $array = array(0, 1, 2, 3);
     * -> variant_a := \TYPO3\CMS\Core\Utility\ArrayUtility::inArray($array, $needle)
     * -> variant_b := in_array($needle, $array)
     * -> variant_c := in_array($needle, $array, TRUE)
     * +---------+-----------+-----------+-----------+
     * | $needle | variant_a | variant_b | variant_c |
     * +---------+-----------+-----------+-----------+
     * | '1a'    | FALSE     | TRUE      | FALSE     |
     * | ''      | FALSE     | TRUE      | FALSE     |
     * | '0'     | TRUE      | TRUE      | FALSE     |
     * | 0       | TRUE      | TRUE      | TRUE      |
     * +---------+-----------+-----------+-----------+
     *
     * @param array $in_array One-dimensional array of items
     * @param string $item Item to check for
     * @return bool TRUE if $item is in the one-dimensional array $in_array
     */
    public static function inArray(array $in_array, $item)
    {
        foreach ($in_array as $val) {
            if (!is_array($val) && (string)$val === (string)$item) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes the value $cmpValue from the $array if found there. Returns the modified array
     *
     * @param array $array Array containing the values
     * @param string $cmpValue Value to search for and if found remove array entry where found.
     * @return array Output array with entries removed if search string is found
     */
    public static function removeArrayEntryByValue(array $array, $cmpValue)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = self::removeArrayEntryByValue($v, $cmpValue);
            } elseif ((string)$v === (string)$cmpValue) {
                unset($array[$k]);
            }
        }
        return $array;
    }

    /**
     * Filters an array to reduce its elements to match the condition.
     * The values in $keepItems can be optionally evaluated by a custom callback function.
     *
     * Example (arguments used to call this function):
     * $array = array(
     * array('aa' => array('first', 'second'),
     * array('bb' => array('third', 'fourth'),
     * array('cc' => array('fifth', 'sixth'),
     * );
     * $keepItems = array('third');
     * $getValueFunc = function($value) { return $value[0]; }
     *
     * Returns:
     * array(
     * array('bb' => array('third', 'fourth'),
     * )
     *
     * @param array $array The initial array to be filtered/reduced
     * @param mixed $keepItems The items which are allowed/kept in the array - accepts array or csv string
     * @param string $getValueFunc (optional) Callback function used to get the value to keep
     * @return array The filtered/reduced array with the kept items
     */
    public static function keepItemsInArray(array $array, $keepItems, $getValueFunc = null)
    {
        if ($array) {
            // Convert strings to arrays:
            if (is_string($keepItems)) {
                $keepItems = GeneralUtility::trimExplode(',', $keepItems);
            }
            // Check if valueFunc can be executed:
            if (!is_callable($getValueFunc)) {
                $getValueFunc = null;
            }
            // Do the filtering:
            if (is_array($keepItems) && !empty($keepItems)) {
                foreach ($array as $key => $value) {
                    // Get the value to compare by using the callback function:
                    $keepValue = isset($getValueFunc) ? call_user_func($getValueFunc, $value) : $value;
                    if (!in_array($keepValue, $keepItems)) {
                        unset($array[$key]);
                    }
                }
            }
        }
        return $array;
    }

    /**
     * Rename Array keys with a given mapping table
     *
     * @param array	$array Array by reference which should be remapped
     * @param array	$mappingTable Array with remap information, array/$oldKey => $newKey)
     */
    public static function remapArrayKeys(array &$array, array $mappingTable)
    {
        foreach ($mappingTable as $old => $new) {
            if ($new && isset($array[$old])) {
                $array[$new] = $array[$old];
                unset($array[$old]);
            }
        }
    }

    /**
     * Filters keys off from first array that also exist in second array. Comparison is done by keys.
     * This method is a recursive version of php array_diff_assoc()
     *
     * @param array $array1 Source array
     * @param array $array2 Reduce source array by this array
     * @return array Source array reduced by keys also present in second array
     */
    public static function arrayDiffAssocRecursive(array $array1, array $array2)
    {
        $differenceArray = [];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $differenceArray[$key] = $value;
            } elseif (is_array($value)) {
                if (is_array($array2[$key])) {
                    $differenceArray[$key] = self::arrayDiffAssocRecursive($value, $array2[$key]);
                }
            }
        }
        return $differenceArray;
    }

    /**
     * Sorts an array by key recursive - uses natural sort order (aAbB-zZ)
     *
     * @param array $array array to be sorted recursively, passed by reference
     * @return bool always TRUE
     */
    public static function naturalKeySortRecursive(array &$array)
    {
        uksort($array, 'strnatcasecmp');
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                self::naturalKeySortRecursive($value);
            }
        }

        return true;
    }
}
