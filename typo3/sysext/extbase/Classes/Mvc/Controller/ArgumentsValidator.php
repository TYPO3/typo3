<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * A validator for controller arguments
 *
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1 Is only needed for old property mapper.
 */
class ArgumentsValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractObjectValidator {

	/**
	 * Checks if the given value (ie. an Arguments object) is valid.
	 *
	 * If at least one error occurred, the result is FALSE and any errors can
	 * be retrieved through the getErrors() method.
	 *
	 * @param object $arguments The arguments object that should be validated
	 * @throws \InvalidArgumentException
	 * @return boolean TRUE if all arguments are valid, FALSE if an error occured
	 */
	public function isValid($arguments) {
		if (!$arguments instanceof \TYPO3\CMS\Extbase\Mvc\Controller\Arguments) {
			throw new \InvalidArgumentException('Expected TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments, ' . gettype($arguments) . ' given.', 1241079561);
		}
		$this->errors = array();
		$result = TRUE;
		foreach ($arguments->getArgumentNames() as $argumentName) {
			if ($this->isPropertyValid($arguments, $argumentName) === FALSE) {
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if this validator can validate instances of the given object or FALSE if it can't
	 */
	public function canValidate($object) {
		return $object instanceof \TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
	}

	/**
	 * Checks if the specified property (ie. the argument) of the given arguments
	 * object is valid. Validity is checked by first invoking the validation chain
	 * defined in the argument object.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $arguments The arguments object containing the property (argument) to validate
	 * @param string $argumentName Name of the property (ie. name of the argument) to validate
	 * @throws \InvalidArgumentException
	 * @return boolean TRUE if the argument is valid, FALSE if an error occured
	 */
	public function isPropertyValid($arguments, $argumentName) {
		if (!$arguments instanceof \TYPO3\CMS\Extbase\Mvc\Controller\Arguments) {
			throw new \InvalidArgumentException('Expected TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments, ' . gettype($arguments) . ' given.', 1241079562);
		}
		$argument = $arguments[$argumentName];
		$validatorConjunction = $argument->getValidator();
		if ($validatorConjunction === NULL) {
			return TRUE;
		}
		$argumentValue = $argument->getValue();
		if ($argumentValue === $argument->getDefaultValue() && $argument->isRequired() === FALSE) {
			return TRUE;
		}
		if ($validatorConjunction->isValid($argumentValue) === FALSE) {
			$this->addErrorsForArgument($validatorConjunction->getErrors(), $argumentName);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Adds the given errors to $this->errors and creates an ArgumentError
	 * instance if needed.
	 *
	 * @param array $errors Array of \TYPO3\CMS\Extbase\Validation\Error
	 * @param string $argumentName Name of the argument to add errors for
	 * @return void
	 */
	protected function addErrorsForArgument(array $errors, $argumentName) {
		if (!isset($this->errors[$argumentName])) {
			$this->errors[$argumentName] = new \TYPO3\CMS\Extbase\Mvc\Controller\ArgumentError($argumentName);
		}
		$this->errors[$argumentName]->addErrors($errors);
	}
}

?>