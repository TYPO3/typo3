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

/**
 * A controller argument
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Argument {

	/**
	 * @var F3_FLOW3_Object_ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var F3_FLOW3_Object_FactoryInterface
	 */
	protected $objectFactory;

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
	 * Short help message for this argument
	 * @var string Short help message for this argument
	 */
	protected $shortHelpMessage = NULL;

	/**
	 * The argument is valid
	 * @var boolean
	 */
	protected $isValid = TRUE;

	/**
	 * Any error (F3_FLOW3_Error_Error) that occured while initializing this argument (e.g. a mapping error)
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Any warning (F3_FLOW3_Error_Warning) that occured while initializing this argument (e.g. a mapping warning)
	 * @var array
	 */
	protected $warnings = array();

	/**
	 * The property validator for this argument
	 * @var F3_FLOW3_Validation_ValidatorInterface
	 */
	protected $validator = NULL;

	/**
	 * The property validator for this arguments datatype
	 * @var F3_FLOW3_Validation_ValidatorInterface
	 */
	protected $datatypeValidator = NULL;

	/**
	 * The filter for this argument
	 * @var F3_FLOW3_Validation_FilterInterface
	 */
	protected $filter = NULL;

	/**
	 * The property converter for this argument
	 * @var F3_FLOW3_Property_ConverterInterface
	 */
	protected $propertyConverter = NULL;

	/**
	 * The property converter's input format for this argument
	 * @var string
	 */
	protected $propertyConverterInputFormat = 'string';

	/**
	 * Identifier for the argument, if it has one
	 * @var string
	 */
	protected $identifier = NULL;

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @param F3_FLOW3_Object_ManagerInterface The object manager
	 * @throws InvalidArgumentException if $name is not a string or empty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, $dataType = 'Text', F3_FLOW3_Object_ManagerInterface $objectManager) {
		if (!is_string($name) || strlen($name) < 1) throw new InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		$this->objectManager = $objectManager;
		$this->objectFactory = $this->objectManager->getObjectFactory();
		$this->name = $name;

		$this->setDataType($dataType);
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Sets the data type of this argument's value
	 *
	 * @param string $dataType: Name of the data type
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDataType($dataType) {
		$this->dataType = ($dataType != '' ? $dataType : 'Text');

		$dataTypeValidatorClassname = $this->dataType;
		if (!$this->objectManager->isObjectRegistered($dataTypeValidatorClassname)) $dataTypeValidatorClassname = 'F3_FLOW3_Validation_Validator\\' . $this->dataType;
		$this->datatypeValidator = $this->objectManager->getObject($dataTypeValidatorClassname);

		return $this;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Marks this argument to be required
	 *
	 * @param boolean $required TRUE if this argument should be required
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setRequired($required) {
		$this->isRequired = $required;
		return $this;
	}

	/**
	 * Returns TRUE if this argument is required
	 *
	 * @return boolean TRUE if this argument is required
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Checks if this argument has a value set.
	 *
	 * @return boolean TRUE if a value was set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValue() {
		return $this->value !== NULL;
	}

	/**
	 * Sets a short help message for this argument. Mainly used at the command line, but maybe
	 * used elsewhere, too.
	 *
	 * @param string $message: A short help message
	 * @return TX_EXTMVC_Controller_Argument		$this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessage($message) {
		if (!is_string($message)) throw new InvalidArgumentException('The help message must be of type string, ' . gettype($message) . 'given.', 1187958170);
		$this->shortHelpMessage = $message;
		return $this;
	}

	/**
	 * Returns the short help message
	 *
	 * @return string The short help message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getShortHelpMessage() {
		return $this->shortHelpMessage;
	}

	/**
	 * Set the validity status of the argument
	 *
	 * @param boolean TRUE if the argument is valid, FALSE otherwise
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setValidity($isValid) {
		$this->isValid = $isValid;
	}

	/**
	 * Returns TRUE when the argument is valid
	 *
	 * @return boolean TRUE if the argument is valid
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Add an initialization error (e.g. a mapping error)
	 *
	 * @param F3_FLOW3_Error_Error An error object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addError(F3_FLOW3_Error_Error $error) {
		$this->errors[] = $error;
	}

	/**
	 * Get all initialization errors
	 *
	 * @return array An array containing F3_FLOW3_Error_Error objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see addError(F3_FLOW3_Error_Error $error)
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Add an initialization warning (e.g. a mapping warning)
	 *
	 * @param F3_FLOW3_Error_Warning A warning object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addWarning(F3_FLOW3_Error_Warning $warning) {
		$this->warnings[] = $warning;
	}

	/**
	 * Get all initialization warnings
	 *
	 * @return array An array containing F3_FLOW3_Error_Warning objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see addWarning(F3_FLOW3_Error_Warning $warning)
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Set an additional validator
	 *
	 * @param string Class name of a validator
	 * @return TX_EXTMVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setValidator($className) {
		$this->validator = $this->objectManager->getObject($className);
		return $this;
	}

	/**
	 * Returns the set validator
	 *
	 * @return F3_FLOW3_Validation_ValidatorInterface The set validator, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Returns the set datatype validator
	 *
	 * @return F3_FLOW3_Validation_ValidatorInterface The set datatype validator
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getDatatypeValidator() {
		return $this->datatypeValidator;
	}

	/**
	 * Set a filter
	 *
	 * @param string Class name of a filter
	 * @return TX_EXTMVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setFilter($className) {
		$this->filter = $this->objectManager->getObject($className);
		return $this;
	}

	/**
	 * Create and set a filter chain
	 *
	 * @param array Class names of the filters
	 * @return TX_EXTMVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChain(array $classNames) {
		$this->filter = $this->createNewFilterChainObject();

		foreach ($classNames as $className) {
			if (!$this->objectManager->isObjectRegistered($className)) $className = 'F3_FLOW3_Validation_Filter\\' . $className;
			$this->filter->addFilter($this->objectManager->getObject($className));
		}

		return $this;
	}

	/**
	 * Create and set a validator chain
	 *
	 * @param array Class names of the validators
	 * @return TX_EXTMVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChain(array $classNames) {
		$this->validator = $this->createNewValidatorChainObject();

		foreach ($classNames as $className) {
			if (!$this->objectManager->isObjectRegistered($className)) $className = 'F3_FLOW3_Validation_Validator\\' . $className;
			$this->validator->addValidator($this->objectManager->getObject($className));
		}

		return $this;
	}

	/**
	 * Returns the set filter
	 *
	 * @return F3_FLOW3_Validation_FilterInterface The set filter, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getFilter() {
		return $this->filter;
	}

	/**
	 * Set a property converter
	 *
	 * @param string Class name of a property converter
	 * @return TX_EXTMVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPropertyConverter($className) {
		if (is_string($className)) {
			$this->propertyConverter = $this->objectFactory->create($className);
		} else {
			$this->propertyConverter = $className;
		}
		return $this;
	}

	/**
	 * Returns the set property converter
	 *
	 * @return F3_FLOW3_Property_ConverterInterface The set property convertr, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPropertyConverter() {
		return $this->propertyConverter;
	}

	/**
	 * Set a property converter input format
	 *
	 * @param string Input format the property converter should use
	 * @return TX_EXTMVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPropertyConverterInputFormat($format) {
		$this->propertyConverterInputFormat = $format;
		return $this;
	}

	/**
	 * Returns the set property converter input format
	 *
	 * @return string The set property converter input format
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPropertyConverterInputFormat() {
		return $this->propertyConverterInputFormat;
	}

	/**
	 * Set the identifier for the argument.
	 *
	 * @param string $identifier The identifier for the argument.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Get the identifier of the argument, if it has one.
	 *
	 * @return string Identifier of the argument. If none set, returns NULL.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Factory method that creates a new filter chain
	 *
	 * @return F3_FLOW3_Validation_Filter_Chain A new filter chain
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createNewFilterChainObject() {
		return $this->objectFactory->create('F3_FLOW3_Validation_Filter_Chain');
	}

	/**
	 * Factory method that creates a new validator chain
	 *
	 * @return F3_FLOW3_Validation_Validator_Chain A new validator chain
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createNewValidatorChainObject() {
		return $this->objectFactory->create('F3_FLOW3_Validation_Validator_Chain');
	}

	/**
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>