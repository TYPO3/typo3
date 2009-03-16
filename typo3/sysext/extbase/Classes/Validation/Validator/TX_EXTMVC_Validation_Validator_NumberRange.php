<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Validation/Validator/TX_EXTMVC_Validation_Validator_ValidatorInterface.php');

/**
 * Validator for general numbers
 *
 * @version $ID:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Validation_Validator_NumberRange implements TX_EXTMVC_Validation_Validator_ValidatorInterface {

	/**
	 * The start value of the range
	 * @var number
	 */
	protected $startRange;

	/**
	 * The end value of the range
	 * @var number
	 */
	protected $endRange;

	/**
	 * Constructor
	 *
	 * @param number The start of the range
	 * @param number The end of the range
	 */
	public function __construct($startRange, $endRange) {
		if ($startRange > $endRange) {
			$this->endRange = $startRange;
			$this->startRange = $endRange;
		} else {
			$this->endRange = $endRange;
			$this->startRange = $startRange;
		}
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid number in the given range.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @param TX_EXTMVC_Validation_Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions Not used
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value, TX_EXTMVC_Validation_Errors &$errors, array $validationOptions = array()) {
		if (!is_numeric($value)) $errors->append('The given subject was not a valid number.');
		if ($value < $this->startRange || $value > $this->endRange) $errors->append('The given subject was not in the valid range (' . $this->startRange . ', ' . $this->endRange . ').');
		if (count($errors) > 0) return FALSE;
		return TRUE;
	}
}

?>