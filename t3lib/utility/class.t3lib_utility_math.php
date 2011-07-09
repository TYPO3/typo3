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
		} // If the input value is zero after being converted to integer, defaultValue may set another default value for it.
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

	/**
	 * Tests if the input can be interpreted as integer.
	 *
	 * @param $var mixed Any input variable to test
	 * @return boolean Returns TRUE if string is an integer
	 */
	public static function canBeInterpretedAsInteger($var) {
		if ($var === '') {
			return FALSE;
		}
		return (string) intval($var) === (string) $var;
	}

	/**
	 * Calculates the input by +,-,*,/,%,^ with priority to + and -
	 *
	 * @param $string string Input string, eg "123 + 456 / 789 - 4"
	 * @return integer Calculated value. Or error string.
	 * @see calcParenthesis()
	 */
	public static function calculateWithPriorityToAdditionAndSubtraction($string) {
		$string = preg_replace('/[[:space:]]*/', '', $string); // removing all whitespace
		$string = '+' . $string; // Ensuring an operator for the first entrance
		$qm = '\*\/\+-^%';
		$regex = '([' . $qm . '])([' . $qm . ']?[0-9\.]*)';
			// split the expression here:
		$reg = array();
		preg_match_all('/' . $regex . '/', $string, $reg);

		reset($reg[2]);
		$number = 0;
		$Msign = '+';
		$err = '';
		$buffer = doubleval(current($reg[2]));
		next($reg[2]); // Advance pointer

		while (list($k, $v) = each($reg[2])) {
			$v = doubleval($v);
			$sign = $reg[1][$k];
			if ($sign == '+' || $sign == '-') {
				$Msign == '-' ? $number -= $buffer : $number += $buffer;
				$Msign = $sign;
				$buffer = $v;
			} else {
				if ($sign == '/') {
					if ($v) {
						$buffer /= $v;
					} else {
						$err = 'dividing by zero';
					}
				}
				if ($sign == '%') {
					if ($v) {
						$buffer %= $v;
					} else {
						$err = 'dividing by zero';
					}
				}
				if ($sign == '*') {
					$buffer *= $v;
				}
				if ($sign == '^') {
					$buffer = pow($buffer, $v);
				}
			}
		}
		$number = $Msign == '-' ? $number -= $buffer : $number += $buffer;
		return $err ? 'ERROR: ' . $err : $number;
	}

	/**
	 * Calculates the input with parenthesis levels
	 *
	 * @param $string string Input string, eg "(123 + 456) / 789 - 4"
	 * @return integer Calculated value. Or error string.
	 * @see calcPriority(), tslib_cObj::stdWrap()
	 */
	public static function calculateWithParentheses($string) {
		$securC = 100;
		do {
			$valueLenO = strcspn($string, '(');
			$valueLenC = strcspn($string, ')');
			if ($valueLenC == strlen($string) || $valueLenC < $valueLenO) {
				$value = self::calculateWithPriorityToAdditionAndSubtraction(substr($string, 0, $valueLenC));
				$string = $value . substr($string, $valueLenC + 1);
				return $string;
			} else {
				$string = substr($string, 0, $valueLenO) . self::calculateWithParentheses(substr($string, $valueLenO + 1));
			}
				// Security:
			$securC--;
			if ($securC <= 0) {
				break;
			}
		} while ($valueLenO < strlen($string));
		return $string;
	}
}

?>