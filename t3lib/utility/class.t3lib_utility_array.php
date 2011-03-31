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
 */
class t3lib_utility_Array {

	/**
	 * Provides a recursive search for a value in an array (returns the key of the first found item)
	 *
	 * @static
	 * @param array $haystack The recursive array in which to search
	 * @param string $needle The string for which to search
	 * @return bool|mixed Returns the key if $needle was found, otherwise false
	 */
	public static function recursiveArraySearch(array $haystack, $needle) {
		$arrayIterator = new RecursiveArrayIterator($haystack);
		$iterator = new RecursiveIteratorIterator($arrayIterator);

		while($iterator->valid()) {
			if($iterator->current() == $needle) {
				return $arrayIterator->key();
			}
			$iterator->next();
		}
		return FALSE;
	}

	/**
	 * Insert a subarray in an array at a certain position
	 * possible positions are: before, after or replace
	 *
	 * @static
	 * @throws InvalidArgumentException
	 * @param array $data The data in which to insert
	 * @param mixed $insertionData The data to insert (could be string or array)
	 * @param string $position The position, where to insert. Colon separated, one of: before,after,replace, f.e. before:field
	 * @return array Returns a (numeric) array with the new data inserted at the specified position
	 */
	public static function insertIntoArrayAtSpecifiedPosition(array $data, $insertionData, $position) {
			//ensure numeric keys
		$data = array_values($data);
		$positionArray = t3lib_div::trimExplode(':', $position);
		if(count($positionArray) === 2) {
			$matchedKey = t3lib_utility_Array::recursiveArraySearch(
				$data,
				$positionArray[1]
			);
			if($positionArray[0] === 'replace') {
				$data[$matchedKey] = $insertionData;
			} else {
				if($positionArray[0] === 'before') {
					$offset = $matchedKey;
				} else {
					if($positionArray[0] === 'after') {
						$offset = $matchedKey + 1;
					}
				}
				array_splice($data, $offset, 0, array(0 => $insertionData));
			}
		} else {
			throw new InvalidArgumentException('Wrong format for $position param.', 1301685132);
		}
		return $data;
	}
}

?>