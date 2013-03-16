<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Susanne Moog <typo3@susanne-moog.de>
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
 */
class MathUtility {

	/**
	 * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is FALSE then the $defaultValue is applied.
	 *
	 * @param integer $theInt Input value
	 * @param integer $min Lower limit
	 * @param integer $max Higher limit
	 * @param integer $defaultValue Default value if input is FALSE.
	 * @return integer The input value forced into the boundaries of $min and $max
	 */
	static public function forceIntegerInRange($theInt, $min, $max = 2000000000, $defaultValue = 0) {
		// Returns $theInt as an integer in the integerspace from $min to $max
		$theInt = intval($theInt);
		// If the input value is zero after being converted to integer,
		// defaultValue may set another default value for it.
		if ($defaultValue && !$theInt) {
			$theInt = $defaultValue;
		}
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
	 * @param integer $theInt Integer string to process
	 * @return integer
	 */
	static public function convertToPositiveInteger($theInt) {
		$theInt = intval($theInt);
		if ($theInt < 0) {
			$theInt = 0;
		}
		return $theInt;
	}

	/**
	 * Tests if the input can be interpreted as integer.
	 *
	 * Note: Integer casting from objects or arrays is considered undefined and thus will return false.
	 *
	 * @see http://php.net/manual/en/language.types.integer.php#language.types.integer.casting.from-other
	 * @param mixed $var Any input variable to test
	 * @return boolean Returns TRUE if string is an integer
	 */
	static public function canBeInterpretedAsInteger($var) {
		if ($var === '' || is_object($var) || is_array($var)) {
			return FALSE;
		}
		return (string) intval($var) === (string) $var;
	}

	/**
	 * Calculates the input by +,-,*,/,%,^ with priority to + and -
	 *
	 * @param string $string Input string, eg "123 + 456 / 789 - 4
	 * @return integer Calculated value. Or error string.
	 * @see \TYPO3\CMS\Core\Utility\MathUtility::calculateWithParentheses()
	 */
	static public function calculateWithPriorityToAdditionAndSubtraction($string) {
		// Removing all whitespace
		$string = preg_replace('/[[:space:]]*/', '', $string);
		// Ensuring an operator for the first entrance
		$string = '+' . $string;
		$qm = '\\*\\/\\+-^%';
		$regex = '([' . $qm . '])([' . $qm . ']?[0-9\\.]*)';
		// Split the expression here:
		$reg = array();
		preg_match_all('/' . $regex . '/', $string, $reg);
		reset($reg[2]);
		$number = 0;
		$Msign = '+';
		$err = '';
		$buffer = doubleval(current($reg[2]));
		// Advance pointer
		next($reg[2]);
		while (list($k, $v) = each($reg[2])) {
			$v = doubleval($v);
			$sign = $reg[1][$k];
			if ($sign == '+' || $sign == '-') {
				$Msign == '-' ? ($number -= $buffer) : ($number += $buffer);
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
		$number = $Msign == '-' ? ($number -= $buffer) : ($number += $buffer);
		return $err ? 'ERROR: ' . $err : $number;
	}

	/**
	 * Calculates the input with parenthesis levels
	 *
	 * @param string $string Input string, eg "(123 + 456) / 789 - 4
	 * @return integer Calculated value. Or error string.
	 * @see calculateWithPriorityToAdditionAndSubtraction(), tslib_cObj::stdWrap()
	 */
	static public function calculateWithParentheses($string) {
		$securC = 100;
		do {
			$valueLenO = strcspn($string, '(');
			$valueLenC = strcspn($string, ')');
			if ($valueLenC == strlen($string) || $valueLenC < $valueLenO) {
				$value = self::calculateWithPriorityToAdditionAndSubtraction(substr($string, 0, $valueLenC));
				$string = $value . substr($string, ($valueLenC + 1));
				return $string;
			} else {
				$string = substr($string, 0, $valueLenO) . self::calculateWithParentheses(substr($string, ($valueLenO + 1)));
			}
			// Security:
			$securC--;
			if ($securC <= 0) {
				break;
			}
		} while ($valueLenO < strlen($string));
		return $string;
	}

	/**
	 * Checks whether the given number $value is an integer in the range [$minimum;$maximum]
	 *
	 * @param integer $value Integer value to check
	 * @param integer $minimum Lower boundary of the range
	 * @param integer $maximum Upper boundary of the range
	 * @return boolean
	 */
	static public function isIntegerInRange($value, $minimum, $maximum) {
		$value = filter_var($value, FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => $minimum,
				'max_range' => $maximum
			)
		));
		$isInRange = is_int($value);
		return $isInRange;
	}

}


?>