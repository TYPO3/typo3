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
 * A generic object validator which allows for specifying property validators
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: GenericObjectValidator.php 1948 2010-03-04 06:40:53Z jocrau $
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_GenericObjectValidator extends Tx_Extbase_Validation_Validator_AbstractObjectValidator {

	/**
	 * @var array
	 */
	protected $propertyValidators = array();

	/**
	 * Checks if the given value is valid according to the property validators
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @api
	 */
	public function isValid($value) {
		if (!is_object($value)) {
			$this->addError('Value is no object.', 1241099148);
			return FALSE;
		}

		$result = TRUE;
		foreach (array_keys($this->propertyValidators) as $propertyName) {
			if ($this->isPropertyValid($value, $propertyName) === FALSE) {
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if the given value is an object
	 * @api
	 */
	public function canValidate($object) {
		return is_object($object);
	}

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @return boolean TRUE if the property value is valid, FALSE if an error occured
	 * @api
	 */
	public function isPropertyValid($object, $propertyName) {
		if (!is_object($object)) throw new InvalidArgumentException('Object expected, ' . gettype($object) . ' given.', 1241099149);
		if (!isset($this->propertyValidators[$propertyName])) return TRUE;

		$result = TRUE;
		foreach ($this->propertyValidators[$propertyName] as $validator) {
			if ($validator->isValid(Tx_Extbase_Reflection_ObjectAccess::getProperty($object, $propertyName)) === FALSE) {
				$this->addErrorsForProperty($validator->getErrors(), $propertyName);
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * @param array $errors Array of Tx_Extbase_Validation_Error
	 * @param string $propertyName Name of the property to add errors
	 * @return void
	 */
	protected function addErrorsForProperty($errors, $propertyName) {
		if (!isset($this->errors[$propertyName])) {
			$this->errors[$propertyName] = t3lib_div::makeInstance('Tx_Extbase_Validation_PropertyError', $propertyName);
		}
		$this->errors[$propertyName]->addErrors($errors);
	}

	/**
	 * Adds the given validator for validation of the specified property.
	 *
	 * @param string $propertyName Name of the property to validate
	 * @param Tx_Extbase_Validation_Validator_ValidatorInterface $validator The property validator
	 * @return void
	 * @api
	 */
	public function addPropertyValidator($propertyName, Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		if (!isset($this->propertyValidators[$propertyName])) {
			$this->propertyValidators[$propertyName] = new Tx_Extbase_Persistence_ObjectStorage;
		}
		$this->propertyValidators[$propertyName]->attach($validator);
	}
}

?>