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
 * A controller argument
 *
 * @package Extbase
 * @subpackage MVC
 * @version $ID:$
 * @scope prototype
 */
class Tx_Extbase_MVC_Controller_Argument {
	
	/**
	 * @var Tx_Extbase_Persistence_QueryFactory
	 */
	protected $queryFactory;
	
	/**
	 * @var Tx_Extbase_Property_Mapper
	 */
	protected $propertyMapper;

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
	 * Default value. Used if argument is optional.
	 * @var mixed
	 */
	protected $defaultValue = NULL;

	/**
	 * A custom validator, used supplementary to the base validation
	 * @var Tx_Extbase_Validation_Validator_ValidatorInterface
	 */
	protected $validator = NULL;

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
		// $this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
		$this->propertyMapper = t3lib_div::makeInstance('Tx_Extbase_Property_Mapper');
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
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @throws InvalidArgumentException if $shortName is not a character
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || strlen($shortName) !== 1)) throw new InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
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
	 * @param string $dataType The data type. Can be either a built-in type such as "Text" or "Integer" or a fully qualified object name
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 */
	public function setDataType($dataType) {
		$this->dataType = $dataType;
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
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 */
	public function setRequired($required) {
		$this->isRequired = (boolean)$required;
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
	 * Sets the default value of the argument
	 *
	 * @param mixed $defaultValue Default value
	 * @return void
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

	/**
	 * Returns the default value of this argument
	 *
	 * @return mixed The default value
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}
	
	/**
	 * Sets a custom validator which is used supplementary to the base validation
	 *
	 * @param Tx_Extbase_Validation_Validator_ValidatorInterface $validator The actual validator object
	 * @return Tx_Extbase_MVC_Controller_Argument Returns $this (used for fluent interface)
	 */
	public function setValidator(Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		$this->validator = $validator;
		return $this;
	}

	/**
	 * Create and set a validator chain
	 *
	 * @param array Object names of the validators
	 * @return Tx_Extbase_MVC_Controller_Argument Returns $this (used for fluent interface)
	 */
	public function setNewValidatorChain(array $objectNames) {
		if ($this->validator === NULL) {
			$this->validator = t3lib_div::makeInstance('Tx_Extbase_Validation_Validator_ChainValidator');
		}
		foreach ($objectNames as $objectName) {
			if (!class_exists($objectName)) $objectName = 'Tx_Extbase_Validation_Validator_' . $objectName;
			$this->validator->addValidator(t3lib_div::makeInstance($objectName));
		}
		return $this;
	}
	/**
	 * Returns the set validator
	 *
	 * @return Tx_Extbase_Validation_Validator_ValidatorInterface The set validator, NULL if none was set
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $value: The value of this argument
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @throws Tx_Extbase_MVC_Exception_InvalidArgumentValue if the argument is not a valid object of type $dataType
	 */
	public function setValue($value) {
		if (is_array($value)) {
			if (isset($value['uid'])) {
				$existingObject = $this->findObjectByUid($value['uid']);
				if ($existingObject === FALSE) throw new Tx_Extbase_MVC_Exception_InvalidArgumentValue('Argument "' . $this->name . '": Querying the repository for the specified object was not sucessful.', 1237305720);
				unset($value['uid']);
				if (count($value) === 0) {
					$value = $existingObject;
				} elseif ($existingObject !== NULL) {
					$newObject = clone $existingObject;
					if ($this->propertyMapper->map(array_keys($value), $value, $newObject)) {
						$value = $newObject;
					}
				}
			} else {
				$newObject = t3lib_div::makeInstance($this->dataType);
				if ($this->propertyMapper->map(array_keys($value), $value, $newObject)) {
					$value = $newObject;
				}
			}
		}
		$this->value = $value;
		return $this;
	}
	
	/**
	 * Finds an object from the repository by searching for its technical UID.
	 *
	 * @param int $uid The object's uid
	 * @return mixed Either the object matching the uid or, if none or more than one object was found, FALSE
	 */
	protected function findObjectByUid($uid) {
		$repositoryClassName = $this->dataType . 'Repository';
		if (class_exists($repositoryClassName)) {
			$repository = t3lib_div::makeInstance($this->dataType . 'Repository');
			$object = $repository->findOneByUid($uid);
		}
		return $object;
		// TODO replace code as soon as the query object is available
		// $query = $this->queryFactory->create($this->dataType);
		// $query->matching('uid=' . intval($uid));
		// $objects = $query->execute();
		// if (count($objects) === 1 ) return current($objects);
		// return FALSE;
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 */
	public function getValue() {
		if ($this->value === NULL) {
			return $this->defaultValue;
		} else {
			return $this->value;
		}
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
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>