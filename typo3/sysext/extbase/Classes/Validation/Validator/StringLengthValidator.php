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
 * Validator for string length
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: StringLengthValidator.php 1052 2009-08-05 21:51:32Z sebastian $
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_StringLengthValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) is a valid string and its length
	 * is between 'minimum' (defaults to 0 if not specified) and 'maximum' (defaults to infinite if not specified)
	 * to be specified in the validation options.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		$this->errors = array();
		if (isset($this->options['minimum']) && isset($this->options['maximum'])
			&& $this->options['maximum'] < $this->options['minimum']) {
			throw new Tx_Extbase_Validation_Exception_InvalidValidationOptions('The \'maximum\' is shorter than the \'minimum\' in the StringLengthValidator.', 1238107096);
		}

		if (is_object($value) && !method_exists($value, '__toString')) throw new Tx_Extbase_Validation_Exception_InvalidSubject('The given object could not be converted to a string.', 1238110957);

		// TODO Use t3lib_cs::strlen() instead; How do we get the charset?
		$stringLength = strlen($value);
		$isValid = TRUE;
		if (isset($this->options['minimum']) && $stringLength < $this->options['minimum']) $isValid = FALSE;
		if (isset($this->options['maximum']) && $stringLength > $this->options['maximum']) $isValid = FALSE;

		if ($isValid === FALSE) {
			if (isset($this->options['minimum']) && isset($this->options['maximum'])) {
				$this->addError('The length of the given string was not between ' . $this->options['minimum'] . ' and ' . $this->options['maximum'] . ' characters.', 1238108067);
			} elseif (isset($this->options['minimum'])) {
				$this->addError('The length of the given string less than ' . $this->options['minimum'] . ' characters.', 1238108068);
			} else {
				$this->addError('The length of the given string exceeded ' . $this->options['maximum'] . ' characters.', 1238108069);
			}
		}

		return $isValid;
	}
}

?>