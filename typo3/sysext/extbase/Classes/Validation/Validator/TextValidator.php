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
 * Validator for text
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: TextValidator.php 1789 2010-01-18 21:31:59Z jocrau $
 */
class Tx_Extbase_Validation_Validator_TextValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid text (contains no XML tags).
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		$this->errors = array();
		if ($value !== filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) {
			$this->addError('The given subject was not a valid text (e.g. contained XML tags).', 1221565786);
			return FALSE;
		}
		return TRUE;
	}
}

?>