<?php
declare(ENCODING = 'utf-8');

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
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Validation/TX_EXTMVC_Validation_Errors.php');

/**
 * Validator for the controller arguments object
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class TX_EXTMVC_controller_ArgumentsValidator {

	/**
	 * @var TX_EXTMVC_Controller_Arguments The registered arguments with the specified property validators
	 */
	protected $registeredArguments;

	/**
	 * Constructor
	 *
	 * @param TX_EXTMVC_Controller_Arguments The registered arguments with the specified property editors
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(TX_EXTMVC_Controller_Arguments $registeredArguments) {
		$this->registeredArguments = $registeredArguments;
	}

	/**
	 * Returns TRUE, if the given argument ($argumentValue) is a valid value for the property ($argumentName) of the class ($className).
	 * Any errors will be stored in the given errors object. If at least one error occurred, the result is FALSE.
	 *
	 * @param string $argumentName: The name of the argument for wich the value should be validated
	 * @param object $argumentValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated for the given property, FALSE if an error occured
	 */
	public function isValidArgument($argumentName, $argumentValue, TX_EXTMVC_Validation_Errors &$errors) {
		$isValid = TRUE;
		if ($this->registeredArguments[$argumentName]->getValidator() != NULL) {
			$isValid &= $this->registeredArguments[$argumentName]->getValidator()->isValidProperty($argumentValue, $errors);
		} elseif ($this->registeredArguments[$argumentName]->getDatatypeValidator() != NULL) {
			$isValid = $this->registeredArguments[$argumentName]->getDatatypeValidator()->isValidProperty($argumentValue, $errors);			
		} else {
			throw new TX_EXTMVC_Validation_NoValidatorFound('No appropriate validator for the argument "' . $argumentName . '" was found.', 1235748909);
		}

		return (boolean)$isValid;
	}
	
	/**
	 * This is a factory method to get a clean validation errors object
	 *
	 * @return TX_EXTMVC_Validation_Errors An empty errors object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewValidationErrorsObject() {
		return t3lib_div::makeInstance('TX_EXTMVC_Validation_Errors');
	}

}
?>