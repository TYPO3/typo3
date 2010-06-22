<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The array functions from the good old t3lib_div plus new code.
 *
 * @package Extbase
 * @subpackage Utility
 * @version $Id: Arrays.php 1992 2010-03-09 21:44:11Z jocrau $
 (robert) I'm not sure yet if we should use this library statically or as a singleton. The latter might be problematic if we use it from the Core classes.
 * @api
 */
class Tx_Extbase_Utility_Arrays {

	/**
	 * Explodes a $string delimited by $delimeter and passes each item in the array through intval().
	 * Corresponds to explode(), but with conversion to integers for all values.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @return array Exploded values, all converted to integers
	 * @api
	 */
	static public function integerExplode($delimiter, $string) {
		$chunksArr = explode($delimiter, $string);
		foreach ($chunksArr as $key => $value) {
			$chunks[$key] = intval($value);
		}
		return $chunks;
	}

	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @param boolean $onlyNonEmptyValues If set, all empty values (='') will NOT be set in output
	 * @return array Exploded values
	 * @api
	 */
	static public function trimExplode($delimiter, $string, $onlyNonEmptyValues = FALSE) {
		$chunksArr = explode($delimiter, $string);
		$newChunksArr = array();
		foreach ($chunksArr as $value) {
			if ($onlyNonEmptyValues === FALSE || strcmp('', trim($value))) {
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
	 * @param array First array
	 * @param array Second array, overruling the first array
	 * @param boolean If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param boolean If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
	 * @return array Resulting array where $secondArray values has overruled $firstArray values
	 * @api
	 */
	static public function arrayMergeRecursiveOverrule(array $firstArray, array $secondArray, $dontAddNewKeys = FALSE, $emptyValuesOverride = TRUE) {
		foreach ($secondArray as $key => $value) {
			if (array_key_exists($key, $firstArray) && is_array($firstArray[$key])) {
				if (is_array($secondArray[$key])) {
					$firstArray[$key] = self::arrayMergeRecursiveOverrule($firstArray[$key], $secondArray[$key], $dontAddNewKeys, $emptyValuesOverride);
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
	 */
	static public function randomizeArrayOrder(array $array) {
		$reorderedArray = array();
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
	 * @return boolean
	 * @api
	 */
	static public function containsMultipleTypes(array $array) {
		if (count($array) > 0) {
			foreach ($array as $key => $value) {
				if (!isset($previousType)) {
					$previousType = gettype($value);
				} else if ($previousType !== gettype($value)) {
					return TRUE;
				}
			}
		}
		return FALSE;
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
	 */
	static public function array_reduce(array $array, $function, $initial = NULL) {
		$accumlator = $initial;
		foreach($array as $value) {
			$accumlator = $function($accumlator, $value);
		}
		return $accumlator;
	}

	/**
	 * Returns the value of a nested array by following the specifed path.
	 *
	 * @param array $array The array to traverse
	 * @param array $path The path to follow, ie. a simple array of keys
	 * @return mixed The value found, NULL if the path didn't exist
	 * @api
	 */
	static public function getValueByPath(array $array, array $path) {
		$key = array_shift($path);
		if (isset($array[$key])) {
			if (count($path) > 0) {
				return (is_array($array[$key])) ? self::getValueByPath($array[$key], $path) : NULL;
			} else {
				return $array[$key];
			}
		} else {
			return NULL;
		}
	}

	/**
	 * Sorts multidimensional arrays by recursively calling ksort on its elements.
	 *
	 * @param array $array the array to sort
	 * @param integer $sortFlags may be used to modify the sorting behavior using these values (see http://www.php.net/manual/en/function.sort.php)
	 * @return boolean TRUE on success, FALSE on failure
	 * @see asort()
	 * @api
	 */
	static public function sortKeysRecursively(array &$array, $sortFlags = NULL) {
		foreach($array as &$value) {
			if (is_array($value)) {
				if (self::sortKeysRecursively($value, $sortFlags) === FALSE) {
					return FALSE;
				}
			}
		}
		return ksort($array, $sortFlags);
	}

}
?>