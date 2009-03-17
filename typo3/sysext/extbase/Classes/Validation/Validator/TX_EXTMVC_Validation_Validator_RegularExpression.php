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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Validation/Validator/TX_EXTMVC_Validation_Validator_ValidatorInterface.php');

/**
 * Validator for regular expressions
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_Validation_Validator_RegularExpression implements TX_EXTMVC_Validation_Validator_ValidatorInterface {

	/**
	 * Returns TRUE, if the given property ($value) matches the given regular expression.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * @param mixed $value The value that should be validated
	 * @param TX_EXTMVC_Validation_Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions Not used
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value, TX_EXTMVC_Validation_Errors &$errors, array $validationOptions = array()) {
		if (!isset($validationOptions['regularExpression'])) {
			$errors->append('The regular expression was empty.');
			return FALSE;
		}
		$result = preg_match($validationOptions['regularExpression'], $value);
		if ($result === 0) {
			$errors->append('The given subject did not match the pattern.');
			return FALSE;
		}
		if ($result === FALSE) {
			$errors->append('The regular expression "' . $validationOptions['regularExpression'] . '" contained an error.');
			return FALSE;
		}
		return TRUE;
	}
}

?>