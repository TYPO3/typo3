<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Validator for general numbers
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_ExtBase_Validation_Validator_NumberRange implements Tx_ExtBase_Validation_Validator_ValidatorInterface {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid number in the given range.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * @param mixed $value The value that should be validated
	 * @param Tx_ExtBase_Validation_Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions Not used
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value, Tx_ExtBase_Validation_Errors &$errors, array $validationOptions = array()) {
		if (!is_numeric($value)) {
			$errors->append('The given subject was not a valid number. Got: "' . $value . '"');
			return FALSE;
		}

		$startRange = (isset($validationOptions['startRange'])) ? intval($validationOptions['startRange']) : 0;
		$endRange = (isset($validationOptions['endRange'])) ? intval($validationOptions['endRange']) : PHP_INT_MAX;
		if ($startRange > $endRange) {
			$x = $startRange;
			$startRange = $endRange;
			$endRange = $x;
		}
		if ($value >= $startRange && $value <= $endRange) return TRUE;

		$errors->append('The given subject was not in the valid range (' . $startRange . ', ' . $endRange . ').');
		return FALSE;
	}
}

?>