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
 * A controller argument
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 * @scope prototype
 */
class TX_EXTMVC_Controller_Argument {

	/**
	 * Name of this argument
	 * @var string
	 */
	protected $name = '';

	/**
	 * Short name of this argument
	 * @var string
	 */
	protected $shortName = NULL;

	/**
	 * Data type of this argument's value
	 * @var string
	 */
	protected $dataType = 'Text';

	/**
	 * TRUE if this argument is required
	 * @var boolean
	 */
	protected $isRequired = FALSE;

	/**
	 * Actual value of this argument
	 * @var object
	 */
	protected $value = NULL;

	/**
	 * The argument is valid
	 * @var boolean
	 */
	protected $isValid = NULL;

	/**
	 * Any error (TX_EXTMVC_Error_Error) that occured while initializing this argument (e.g. a mapping error)
	 * @var array
	 */
	protected $errors = array();

	/**
	 * The property validator for this argument
	 * @var TX_EXTMVC_Validation_Validator_ValidatorInterface
	 */
	protected $validator = NULL;

	/**
	 * The property validator for this arguments datatype
	 * @var TX_EXTMVC_Validation_Validator_ValidatorInterface
	 */
	// TODO Remove DatatypeValidator
	protected $datatypeValidator = NULL;

	/**
	 * Uid for the argument, if it has one
	 * @var string
	 */
	protected $uid = NULL;

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @throws InvalidArgumentException if $name is not a string or empty
	 */
	public function __construct($name, $dataType = 'Text') {
		if (!is_string($name) || strlen($name) < 1) throw new InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		$this->name = $name;
		if (is_array($dataType)) {
			$this->setNewValidatorChain($dataType);
		} else {
			$this->setDataType($dataType);
		}
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the short name of this argument.
	 *
	 * @param string $shortName A "short name" - a single character
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @throws InvalidArgumentException if $shortName is not a character
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || strlen($shortName) != 1)) throw new InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
		$this->shortName = $shortName;
		return $this;
	}

	/**
	 * Returns the short name of this argument
	 *
	 * @return string This argument's short name
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Sets the data type of this argument's value
	 *
	 * @param string $dataType: Name of the data type
	 * @return TX_EXTMVC_Controller_Argument $this
	 */
	public function setDataType($dataType) {
		$this->dataType = ($dataType != '' ? $dataType : 'Text');
		// TODO Make validator path and class names configurable
		$dataTypeValidatorClassName = 'TX_EXTMVC_Validation_Validator_' . $this->dataType;
		$classFilePathAndName = t3lib_extMgm::extPath('extmvc') . 'Classes/Validation/Validator/' . $this->dataType . '.php';
		if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {			
			require_once($classFilePathAndName);
			$this->datatypeValidator = t3lib_div::makeInstance($dataTypeValidatorClassName);
		}
		return $this;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Marks this argument to be required
	 *
	 * @param boolean $required TRUE if this argument should be required
	 * @return TX_EXTMVC_Controller_Argument $this
	 */
	public function setRequired($required) {
		$this->isRequired = $required;
		return $this;
	}

	/**
	 * Returns TRUE if this argument is required
	 *
	 * @return boolean TRUE if this argument is required
	 */
	public function isRequired() {
		return $this->isRequired;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $value: The value of this argument
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @throws TX_EXTMVC_Exception_InvalidArgumentValue if the argument is not a valid object of type $dataType
	 */
	public function setValue($value) {
		if ($this->isValidValueForThisArgument($value)) {
			$this->value = $value;
		}
		return $this;
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Checks if this argument has a value set.
	 *
	 * @return boolean TRUE if a value was set, otherwise FALSE
	 */
	public function isValue() {
		return $this->value !== NULL;
	}

	/**
	 * undocumented function
	 *
	 * @param string $value 
	 * @return boolean TRUE if the value is valid for this argument, otherwise FALSE
	 */
	protected function isValidValueForThisArgument($value) {
		$isValid = TRUE;
		$validatorErrors = t3lib_div::makeInstance('TX_EXTMVC_Validation_Errors');
		// TODO use only Validator; do not distinguish between Validator and DatatypeValidator
		if ($this->getValidator() !== NULL) {
			$isValid &= $this->getValidator()->isValid($value, $validatorErrors);
		} elseif ($this->getDatatypeValidator() !== NULL) {
			$isValid = $this->getDatatypeValidator()->isValid($value, $validatorErrors);			
		} else {
			throw new TX_EXTMVC_Validation_Exception_NoValidatorFound('No appropriate validator for the argument "' . $this->getName() . '" was found.', 1235748909);
		}
		if (!$isValid) {
			foreach ($validatorErrors as $error) {
				$this->addError($error);
			}
		}
		$this->isValid = $isValid;
		return (boolean)$isValid;
	}

	/**
	 * Returns TRUE when the argument is valid
	 *
	 * @return boolean TRUE if the argument is valid
	 */
	public function isValid() {
		return $this->isValid;
	}
	
	/**
	 * Add an initialization error (e.g. a mapping error)
	 *
	 * @param string An error text
	 * @return void
	 */
	public function addError($error) {
		$this->errors[] = $error;
	}

	/**
	 * Get all initialization errors
	 *
	 * @return array An array containing TX_EXTMVC_Error_Error objects
	 * @see addError(TX_EXTMVC_Error_Error $error)
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Set an additional validator
	 *
	 * @param string Class name of a validator
	 * @return TX_EXTMVC_MVC_Controller_Argument Returns $this (used for fluent interface)
	 */
	public function setValidator($className) {
		$this->validator = t3lib_div::makeInstance($className);
		return $this;
	}

	/**
	 * Returns the set validator
	 *
	 * @return TX_EXTMVC_Validation_Validator_ValidatorInterface The set validator, NULL if none was set
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Returns the set datatype validator
	 *
	 * @return TX_EXTMVC_Validation_Validator_ValidatorInterface The set datatype validator
	 */
	public function getDatatypeValidator() {
		return $this->datatypeValidator;
	}
	
	/**
	 * Create and set a validator chain
	 *
	 * @param array Object names of the validators
	 * @return TX_EXTMVC_MVC_Controller_Argument Returns $this (used for fluent interface)
	 */
	public function setNewValidatorChain(array $validators) {
		$this->validator = t3lib_div::makeInstance('TX_EXTMVC_Validation_Validator_ChainValidator');
		foreach ($validators as $validator) {
			if (is_array($validator)) {
				$objectName = 'TX_EXTMVC_Validation_Validator_' . $validator[0];
				$this->validator->addValidator(new $objectName);
			} else {
				$objectName = 'TX_EXTMVC_Validation_Validator_' . $validator;
				$this->validator->addValidator(t3lib_div::makeInstance($objectName));
			}
		}
		return $this;
	}
	
	/**
	 * Set the uid for the argument.
	 *
	 * @param string $uid The uid for the argument.
	 * @return void
	 */
	public function setUid($uid) {
		$this->uid = $uid;
	}

	/**
	 * Get the uid of the argument, if it has one.
	 *
	 * @return string Uid of the argument. If none set, returns NULL.
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>