<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Susanne Moog <typo3@susanne-moog.de>
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
 * Class with helper functions for mathematical calculations
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 * @package TYPO3
 * @subpackage t3lib
 */

final class t3lib_utility_Math {

	/**
	 * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is FALSE then the $defaultValue is applied.
	 *
	 * @param $theInt integer Input value
	 * @param $min integer Lower limit
	 * @param $max integer Higher limit
	 * @param $defaultValue integer Default value if input is FALSE.
	 * @return integer The input value forced into the boundaries of $min and $max
	 */
	public static function forceIntegerInRange($theInt, $min, $max = 2000000000, $defaultValue = 0) {
			// Returns $theInt as an integer in the integerspace from $min to $max
		$theInt = intval($theInt);
		if ($defaultValue && !$theInt) {
			$theInt = $defaultValue;
		} // If the input value is zero after being converted to integer, zeroValue may set another default value for it.
		if ($theInt < $min) {
			$theInt = $min;
		}
		if ($theInt > $max) {
			$theInt = $max;
		}
		return $theInt;
	}

	/**
	 * Returns $theInt if it is greater than zero, otherwise returns zero.
	 *
	 * @param $theInt integer Integer string to process
	 * @return integer
	 */
	public static function convertToPositiveInteger($theInt) {
		$theInt = intval($theInt);
		if ($theInt < 0) {
			$theInt = 0;
		}
		return $theInt;
	}
}

?>