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
}

?>