<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Susanne Moog <typo3@susanne-moog.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class with helper functions for array handling
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_utility_Array {

	/**
	 * Reduce an array by a search value and keep the array structure.
	 *
	 * Comparison is type strict:
	 * - For a given needle of type string, integer, array or boolean,
	 * value and value type must match to occur in result array
	 * - For a given object, a object within the array must be a reference to
	 * the same object to match (not just different instance of same class)
	 *
	 * Example:
	 * - Needle: 'findMe'
	 * - Given array:
	 * 	array(
	 * 		'foo' => 'noMatch',
	 * 		'bar' => 'findMe',
	 * 		'foobar => array(
	 * 			'foo' => 'findMe',
	 * 		),
	 * 	);
	 * - Result:
	 * 	array(
	 * 		'bar' => 'findMe',
	 * 		'foobar' => array(
	 * 			'foo' => findMe',
	 * 		),
	 * 	);
	 *
	 * See the unit tests for more examples and expected behaviour
	 *
	 * @static
	 * @param mixed $needle The value to search for
	 * @param array $haystack The array in which to search
	 * @return array $haystack array reduced matching $needle values
	 */
	public static function filterByValueRecursive($needle = '', array $haystack = array()) {
		$resultArray = array();

			// Define a lambda function to be applied to all members of this array dimension
			// Call recursive if current value is of type array
			// Write to $resultArray (by reference!) if types and value match
		$callback = function(&$value, $key) use ($needle, &$resultArray) {
			if ($value === $needle) {
				$resultArray[$key] = $value;
			} elseif (is_array($value)) {
					// self does not work in lambda functions, use t3lib_utility_Array for recursion
				$subArrayMatches = t3lib_utility_Array::filterByValueRecursive($needle, $value);
				if (count($subArrayMatches) > 0) {
					$resultArray[$key] = $subArrayMatches;
				}
			}
		};

			// array_walk() is not affected by the internal pointers, no need to reset
		array_walk($haystack, $callback);

			// Pointers to result array are reset internally
		return $resultArray;
	}

	/**
	 * Check if an string item exists in an array.
	 * Please note that the order of function parameters is reverse compared to the PHP function in_array()!!!
	 *
	 * Comparison to PHP in_array():
	 * -> $array = array(0, 1, 2, 3);
	 * -> variant_a := t3lib_div::inArray($array, $needle)
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
	 * @param array $haystack one-dimensional array of items
	 * @param string $needle item to check for
	 * @return boolean TRUE if $item is in the one-dimensional array $in_array
	 */
	public static function inArray(array $haystack, $needle) {
		foreach ($haystack as $val) {
			if (!is_array($val) && !strcmp($val, $needle)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Explodes a $string delimited by $delim and passes each item in the array through intval().
	 * Corresponds to t3lib_div::trimExplode(), but with conversion to integers for all values.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @param boolean $onlyNonEmptyValues If set, all empty values (='') will NOT be set in output
	 * @param integer $limit If positive, the result will contain a maximum of limit elements,
	 *						 if negative, all components except the last -limit are returned,
	 *						 if zero (default), the result is not limited at all
	 * @return array Exploded values, all converted to integers
	 */
	public static function integerExplode($delimiter, $string, $onlyNonEmptyValues = FALSE, $limit = 0) {
		$explodedValues = self::trimExplode($delimiter, $string, $onlyNonEmptyValues, $limit);
		return array_map('intval', $explodedValues);
	}

	/**
	 * Reverse explode which explodes the string counting from behind.
	 * Thus t3lib_div::revExplode(':','my:words:here',2) will return array('my:words','here')
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @param integer $count Number of array entries
	 * @return array Exploded values
	 */
	public static function reverseExplode($delimiter, $string, $count = 0) {
		$explodedValues = explode($delimiter, strrev($string), $count);
		$explodedValues = array_map('strrev', $explodedValues);
		return array_reverse($explodedValues);
	}

	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @param boolean $removeEmptyValues If set, all empty values will be removed in output
	 * @param integer $limit If positive, the result will contain a maximum of
	 *						 $limit elements, if negative, all components except
	 *						 the last -$limit are returned, if zero (default),
	 *						 the result is not limited at all. Attention though
	 *						 that the use of this parameter can slow down this
	 *						 function.
	 * @return array Exploded values
	 */
	public static function trimExplode($delimiter, $string, $removeEmptyValues = FALSE, $limit = 0) {
		$explodedValues = explode($delimiter, $string);

		$result = array_map('trim', $explodedValues);

		if ($removeEmptyValues) {
			$temp = array();
			foreach ($result as $value) {
				if ($value !== '') {
					$temp[] = $value;
				}
			}
			$result = $temp;
		}

		if ($limit != 0) {
			if ($limit < 0) {
				$result = array_slice($result, 0, $limit);
			} elseif (count($result) > $limit) {
				$lastElements = array_slice($result, $limit - 1);
				$result = array_slice($result, 0, $limit - 1);
				$result[] = implode($delimiter, $lastElements);
			}
		}

		return $result;
	}
}

?>