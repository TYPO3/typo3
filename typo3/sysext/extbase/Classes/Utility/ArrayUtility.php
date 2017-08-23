<?php
namespace TYPO3\CMS\Extbase\Utility;

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
 * The array functions from good old GeneralUtility plus new code.
 * This class has been deprecated as PHP's native functionality and the Core's own ArrayUtility
 * provides the same functionality.
 * Do not use it anymore, it will be removed in TYPO3 v9.
 *
 * @api
 */
class ArrayUtility
{
    /**
     * Explodes a $string delimited by $delimiter and casts each item in the array to (int).
     * Corresponds to explode(), but with conversion to integers for all values.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @return array Exploded values, all converted to integers
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use GeneralUtility::intExplode()
     */
    public static function integerExplode($delimiter, $string)
    {
        GeneralUtility::logDeprecatedFunction();
        $explodedValues = explode($delimiter, $string);
        foreach ($explodedValues as &$value) {
            $value = (int)$value;
        }
        unset($value);
        return $explodedValues;
    }

    /**
     * Explodes a string and trims all values for whitespace in the ends.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @param bool $onlyNonEmptyValues If set, all empty values (='') will NOT be set in output
     * @return array Exploded values
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use GeneralUtility::trimExplode() instead
     */
    public static function trimExplode($delimiter, $string, $onlyNonEmptyValues = false)
    {
        GeneralUtility::logDeprecatedFunction();
        $chunksArr = explode($delimiter, $string);
        $newChunksArr = [];
        foreach ($chunksArr as $value) {
            if ($onlyNonEmptyValues === false || trim($value) !== '') {
                $newChunksArr[] = trim($value);
            }
        }
        reset($newChunksArr);
        return $newChunksArr;
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($firstArray) with the values of the second array ($secondArray)
     * In case of identical keys, ie. keeping the values of the second.
     *
     * @param array $firstArray First array
     * @param array $secondArray Second array, overruling the first array
     * @param bool $dontAddNewKeys If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
     * @param bool $emptyValuesOverride If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
     * @return array Resulting array where $secondArray values has overruled $firstArray values
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use array_replace_recursive() instead if possible, other see the ArrayUtility in EXT:core
     */
    public static function arrayMergeRecursiveOverrule(array $firstArray, array $secondArray, $dontAddNewKeys = false, $emptyValuesOverride = true)
    {
        GeneralUtility::logDeprecatedFunction();
        foreach ($secondArray as $key => $value) {
            if (array_key_exists($key, $firstArray) && is_array($firstArray[$key])) {
                if (is_array($secondArray[$key])) {
                    $firstArray[$key] = self::arrayMergeRecursiveOverrule($firstArray[$key], $secondArray[$key], $dontAddNewKeys, $emptyValuesOverride);
                } else {
                    $firstArray[$key] = $secondArray[$key];
                }
            } else {
                if ($dontAddNewKeys) {
                    if (array_key_exists($key, $firstArray)) {
                        if ($emptyValuesOverride || !empty($value)) {
                            $firstArray[$key] = $value;
                        }
                    }
                } else {
                    if ($emptyValuesOverride || !empty($value)) {
                        $firstArray[$key] = $value;
                    }
                }
            }
        }
        reset($firstArray);
        return $firstArray;
    }

    /**
     * Randomizes the order of array values. The array should not be an associative array
     * as the key-value relations will be lost.
     *
     * @param array $array Array to reorder
     * @return array The array with randomly ordered values
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public static function randomizeArrayOrder(array $array)
    {
        GeneralUtility::logDeprecatedFunction();
        $reorderedArray = [];
        if (count($array) > 1) {
            $keysInRandomOrder = array_rand($array, count($array));
            foreach ($keysInRandomOrder as $key) {
                $reorderedArray[] = $array[$key];
            }
        } else {
            $reorderedArray = $array;
        }
        return $reorderedArray;
    }

    /**
     * Returns TRUE if the given array contains elements of varying types
     *
     * @param array $array
     * @return bool
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public static function containsMultipleTypes(array $array)
    {
        GeneralUtility::logDeprecatedFunction();
        if (!empty($array)) {
            foreach ($array as $value) {
                if (!isset($previousType)) {
                    $previousType = gettype($value);
                } elseif ($previousType !== gettype($value)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Replacement for array_reduce that allows any type for $initial (instead
     * of only integer)
     *
     * @param array $array the array to reduce
     * @param string $function the reduce function with the same order of parameters as in the native array_reduce (i.e. accumulator first, then current array element)
     * @param mixed $initial the initial accumulator value
     * @return mixed
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public static function array_reduce(array $array, $function, $initial = null)
    {
        GeneralUtility::logDeprecatedFunction();
        $accumlator = $initial;
        foreach ($array as $value) {
            $accumlator = $function($accumlator, $value);
        }
        return $accumlator;
    }

    /**
     * Returns the value of a nested array by following the specified path.
     *
     * @param array &$array The array to traverse as a reference
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @throws \InvalidArgumentException
     * @return mixed The value found, NULL if the path didn't exist
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use ArrayUtility provided by EXT:core instead.
     */
    public static function getValueByPath(array &$array, $path)
    {
        GeneralUtility::logDeprecatedFunction();
        if (is_string($path)) {
            $path = explode('.', $path);
        } elseif (!is_array($path)) {
            throw new \InvalidArgumentException('getValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1304950007);
        }
        $key = array_shift($path);
        if (isset($array[$key])) {
            if (!empty($path)) {
                return is_array($array[$key]) ? self::getValueByPath($array[$key], $path) : null;
            }
            return $array[$key];
        }
        return null;
    }

    /**
     * Sets the given value in a nested array or object by following the specified path.
     *
     * @param array|\ArrayAccess $subject The array or ArrayAccess instance to work on
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @param mixed $value The value to set
     * @throws \InvalidArgumentException
     * @return array The modified array or object
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use ArrayUtility provided by EXT:core instead.
     */
    public static function setValueByPath($subject, $path, $value)
    {
        GeneralUtility::logDeprecatedFunction();
        if (!is_array($subject) && !$subject instanceof \ArrayAccess) {
            throw new \InvalidArgumentException('setValueByPath() expects $subject to be array or an object implementing \\ArrayAccess, "' . (is_object($subject) ? get_class($subject) : gettype($subject)) . '" given.', 1306424308);
        }
        if (is_string($path)) {
            $path = explode('.', $path);
        } elseif (!is_array($path)) {
            throw new \InvalidArgumentException('setValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1305111499);
        }
        $key = array_shift($path);
        if (empty($path)) {
            $subject[$key] = $value;
        } else {
            if (!isset($subject[$key]) || !is_array($subject[$key])) {
                $subject[$key] = [];
            }
            $subject[$key] = self::setValueByPath($subject[$key], $path, $value);
        }
        return $subject;
    }

    /**
     * Unsets an element/part of a nested array by following the specified path.
     *
     * @param array $array The array
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @throws \InvalidArgumentException
     * @return array The modified array
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, see ArrayUtility::removeByPath()
     */
    public static function unsetValueByPath(array $array, $path)
    {
        GeneralUtility::logDeprecatedFunction();
        if (is_string($path)) {
            $path = explode('.', $path);
        } elseif (!is_array($path)) {
            throw new \InvalidArgumentException('unsetValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1305111513);
        }
        $key = array_shift($path);
        if (empty($path)) {
            unset($array[$key]);
        } else {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $array;
            }
            $array[$key] = self::unsetValueByPath($array[$key], $path);
        }
        return $array;
    }

    /**
     * Sorts multidimensional arrays by recursively calling ksort on its elements.
     *
     * @param array &$array the array to sort
     * @param int $sortFlags may be used to modify the sorting behavior using these values (see http://www.php.net/manual/en/function.sort.php)
     * @return bool TRUE on success, FALSE on failure
     * @see asort()
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public static function sortKeysRecursively(array &$array, $sortFlags = null)
    {
        GeneralUtility::logDeprecatedFunction();
        foreach ($array as &$value) {
            if (is_array($value)) {
                if (self::sortKeysRecursively($value, $sortFlags) === false) {
                    return false;
                }
            }
        }
        return ksort($array, $sortFlags);
    }

    /**
     * Recursively convert an object hierarchy into an associative array.
     *
     * @param mixed $subject An object or array of objects
     * @throws \InvalidArgumentException
     * @return array The subject represented as an array
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public static function convertObjectToArray($subject)
    {
        GeneralUtility::logDeprecatedFunction();
        if (!is_object($subject) && !is_array($subject)) {
            throw new \InvalidArgumentException('convertObjectToArray expects either array or object as input, ' . gettype($subject) . ' given.', 1287059709);
        }
        if (is_object($subject)) {
            $subject = (array)$subject;
        }
        foreach ($subject as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $subject[$key] = self::convertObjectToArray($value);
            }
        }
        return $subject;
    }

    /**
     * Recursively removes empty array elements.
     *
     * @param array $array
     * @return array the modified array
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public static function removeEmptyElementsRecursively(array $array)
    {
        GeneralUtility::logDeprecatedFunction();
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::removeEmptyElementsRecursively($value);
                if ($result[$key] === []) {
                    unset($result[$key]);
                }
            } elseif ($value === null) {
                unset($result[$key]);
            }
        }
        return $result;
    }

    /**
     * If the array contains numerical keys only, sort it in ascending order
     *
     * @param array $array
     *
     * @return array
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use the same method provided in TYPO3\CMS\Core\Utility\ArrayUtility
     */
    public static function sortArrayWithIntegerKeys($array)
    {
        GeneralUtility::logDeprecatedFunction();
        $containsNumericalKeysOnly = true;
        array_walk($array, function ($value, $key) use (&$containsNumericalKeysOnly) {
            if (!is_int($key)) {
                $containsNumericalKeysOnly = false;
                return;
            }
        });
        if ($containsNumericalKeysOnly === true) {
            ksort($array);
        }
        return $array;
    }
}
