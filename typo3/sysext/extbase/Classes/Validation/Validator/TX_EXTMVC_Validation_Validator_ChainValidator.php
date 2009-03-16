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
 * Validator to chain many validators
 *
 * @version $ID:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class TX_EXTMVC_Validation_Validator_ChainValidator implements TX_EXTMVC_Validation_Validator_ValidatorInterface {

	/**
	 * @var array
	 */
	protected $validators = array();

	/**
	 * Checks if the given value is valid according to the validators of the chain..
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
		$subjectIsValid = TRUE;
		foreach ($this->validators as $validator) {
			$subjectIsValid &= $validator->isValid($value, $errors);
		}
		return (boolean)$subjectIsValid;
	}

	/**
	 * Adds a new validator to the chain. Returns the index of the chain entry.
	 *
	 * @param TX_EXTMVC_Validation_Validator_ValidatorInterface $validator The validator that should be added
	 * @return integer The index of the new chain entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addValidator(TX_EXTMVC_Validation_Validator_ValidatorInterface $validator) {
		$this->validators[] = $validator;
		return count($this->validators) - 1;
	}

	/**
	 * Returns the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be returned
	 * @return TX_EXTMVC_Validation_Validator_ValidatorInterface The requested validator
	 * @throws TX_EXTMVC_Validation_Exception_InvalidChainIndex
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator($index) {
		if (!isset($this->validators[$index])) throw new TX_EXTMVC_Validation_Exception_InvalidChainIndex('Invalid chain index.', 1207215864);
		return $this->validators[$index];
	}

	/**
	 * Removes the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeValidator($index) {
		if (!isset($this->validators[$index])) throw new TX_EXTMVC_Validation_Exception_InvalidChainIndex('Invalid chain index.', 1207020177);
		unset($this->validators[$index]);
	}
}

?>