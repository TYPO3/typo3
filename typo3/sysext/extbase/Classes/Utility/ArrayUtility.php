<?php
namespace TYPO3\CMS\Extbase\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * The array functions from good old GeneralUtility plus new code.
 *
 * @api
 */
class ArrayUtility {

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
		$explodedValues = self::trimExplode($delimiter, $string);
		return array_map('intval', $explodedValues);
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
	 * @param array $firstArray First array
	 * @param array $secondArray Second array, overruling the first array
	 * @param boolean $dontAddNewKeys If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param boolean $emptyValuesOverride If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
	 * @return array Resulting array where $secondArray values has overruled $firstArray values
	 * @api
	 */
	static public function arrayMergeRecursiveOverrule(array $firstArray, array $secondArray, $dontAddNewKeys = FALSE, $emptyValuesOverride = TRUE) {
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
			foreach ($array as $value) {
				if (!isset($previousType)) {
					$previousType = gettype($value);
				} elseif ($previousType !== gettype($value)) {
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
		foreach ($array as $value) {
			$accumlator = $function($accumlator, $value);
		}
		return $accumlator;
	}

	/**
	 * Returns the value of a nested array by following the specifed path.
	 *
	 * @param array &$array The array to traverse as a reference
	 * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
	 * @throws \InvalidArgumentException
	 * @return mixed The value found, NULL if the path didn't exist
	 * @api
	 */
	static public function getValueByPath(array &$array, $path) {
		if (is_string($path)) {
			$path = explode('.', $path);
		} elseif (!is_array($path)) {
			throw new \InvalidArgumentException('getValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1304950007);
		}
		$key = array_shift($path);
		if (isset($array[$key])) {
			if (count($path) > 0) {
				return is_array($array[$key]) ? self::getValueByPath($array[$key], $path) : NULL;
			} else {
				return $array[$key];
			}
		} else {
			return NULL;
		}
	}

	/**
	 * Sets the given value in a nested array or object by following the specified path.
	 *
	 * @param array|\ArrayAccess $subject The array or ArrayAccess instance to work on
	 * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
	 * @param mixed $value The value to set
	 * @throws \InvalidArgumentException
	 * @return array The modified array or object
	 */
	static public function setValueByPath($subject, $path, $value) {
		if (!is_array($subject) && !$subject instanceof \ArrayAccess) {
			throw new \InvalidArgumentException('setValueByPath() expects $subject to be array or an object implementing \\ArrayAccess, "' . (is_object($subject) ? get_class($subject) : gettype($subject)) . '" given.', 1306424308);
		}
		if (is_string($path)) {
			$path = explode('.', $path);
		} elseif (!is_array($path)) {
			throw new \InvalidArgumentException('setValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1305111499);
		}
		$key = array_shift($path);
		if (count($path) === 0) {
			$subject[$key] = $value;
		} else {
			if (!isset($subject[$key]) || !is_array($subject[$key])) {
				$subject[$key] = array();
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
	 */
	static public function unsetValueByPath(array $array, $path) {
		if (is_string($path)) {
			$path = explode('.', $path);
		} elseif (!is_array($path)) {
			throw new \InvalidArgumentException('unsetValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1305111513);
		}
		$key = array_shift($path);
		if (count($path) === 0) {
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
	 * @param integer $sortFlags may be used to modify the sorting behavior using these values (see http://www.php.net/manual/en/function.sort.php)
	 * @return boolean TRUE on success, FALSE on failure
	 * @see asort()
	 * @api
	 */
	static public function sortKeysRecursively(array &$array, $sortFlags = NULL) {
		foreach ($array as &$value) {
			if (is_array($value)) {
				if (self::sortKeysRecursively($value, $sortFlags) === FALSE) {
					return FALSE;
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
	 */
	static public function convertObjectToArray($subject) {
		if (!is_object($subject) && !is_array($subject)) {
			throw new \InvalidArgumentException('convertObjectToArray expects either array or object as input, ' . gettype($subject) . ' given.', 1287059709);
		}
		if (is_object($subject)) {
			$subject = (array) $subject;
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
	 */
	static public function removeEmptyElementsRecursively(array $array) {
		$result = $array;
		foreach ($result as $key => $value) {
			if (is_array($value)) {
				$result[$key] = self::removeEmptyElementsRecursively($value);
				if ($result[$key] === array()) {
					unset($result[$key]);
				}
			} elseif ($value === NULL) {
				unset($result[$key]);
			}
		}
		return $result;
	}
}

?>