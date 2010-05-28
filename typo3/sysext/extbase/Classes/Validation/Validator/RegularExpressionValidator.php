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
 * Validator based on regular expressions
 *
 * The regular expression is specified in the options by using the array key "regularExpression"
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: RegularExpressionValidator.php 1789 2010-01-18 21:31:59Z jocrau $
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_RegularExpressionValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) matches the given regular expression.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		$this->errors = array();
		if (!isset($this->options['regularExpression'])) {
			$this->addError('The regular expression was empty.', 1221565132);
			return FALSE;
		}
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError('The given subject did not match the pattern.', 1221565130);
			return FALSE;
		}
		if ($result === FALSE) {
			$this->addError('The regular expression "' . $this->options['regularExpression'] . '" contained an error.', 1221565131);
			return FALSE;
		}
		return TRUE;
	}
}

?>