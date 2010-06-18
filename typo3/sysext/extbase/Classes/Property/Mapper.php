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
 * The Property Mapper maps properties from a source onto a given target object, often a
 * (domain-) model. Which properties are required and how they should be filtered can
 * be customized.
 *
 * During the mapping process, the property values are validated and the result of this
 * validation can be queried.
 *
 * The following code would map the property of the source array to the target:
 *
 * $target = new ArrayObject();
 * $source = new ArrayObject(
 *    array(
 *       'someProperty' => 'SomeValue'
 *    )
 * );
 * $mapper->mapAndValidate(array('someProperty'), $source, $target);
 *
 * Now the target object equals the source object.
 *
 * @package Extbase
 * @subpackage Property
 * @version $Id: Mapper.php 2259 2010-04-29 07:53:46Z jocrau $
 * @api
 */
class Tx_Extbase_Property_Mapper {

	/**
	 * Results of the last mapping operation
	 * @var Tx_Extbase_Property_MappingResults
	 */
	protected $mappingResults;

	/**
	 * @var Tx_Extbase_Validation_ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_QueryFactory
	 */
	protected $queryFactory;

	/**
	 * Constructs the Property Mapper.
	 */
	public function __construct() {
		// TODO Clean up this dependencies; inject the instance
		$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_Manager');
		$this->validatorResolver = t3lib_div::makeInstance('Tx_Extbase_Validation_ValidatorResolver');
		$this->validatorResolver->injectObjectManager($objectManager);
		$this->persistenceManager = Tx_Extbase_Dispatcher::getPersistenceManager();
		$this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param Tx_Extbase_Reflection_Service
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Maps the given properties to the target object and validates the properties according to the defined
	 * validators. If the result object is not valid, the operation will be undone (the target object remains
	 * unchanged) and this method returns FALSE.
	 *
	 * If in doubt, always prefer this method over the map() method because skipping validation can easily become
	 * a security issue.
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param object $target The target object
	 * @param Tx_Extbase_Validation_Validator_ObjectValidatorInterface $targetObjectValidator A validator used for validating the target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the mapped properties are valid, otherwise FALSE
	 * @see getMappingResults()
	 * @see map()
	 * @api
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), Tx_Extbase_Validation_Validator_ObjectValidatorInterface $targetObjectValidator) {
		$backupProperties = array();

		$this->map($propertyNames, $source, $backupProperties, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		$this->map($propertyNames, $source, $target, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		if ($targetObjectValidator->isValid($target) !== TRUE) {
			$this->addErrorsFromObjectValidator($targetObjectValidator->getErrors());
			$backupMappingResult = $this->mappingResults;
			$this->map($propertyNames, $backupProperties, $source, $optionalPropertyNames);
			$this->mappingResults = $backupMappingResult;
		}
		return (!$this->mappingResults->hasErrors());
	}

	/**
	 * Add errors to the mapping result from an object validator (property errors).
	 *
	 * @param array Array of Tx_Extbase_Validation_PropertyError
	 * @return void
	 */
	protected function addErrorsFromObjectValidator($errors) {
		foreach ($errors as $error) {
			if ($error instanceof Tx_Extbase_Validation_PropertyError) {
				$propertyName = $error->getPropertyName();
				$this->mappingResults->addError($error, $propertyName);
			}
		}
	}

	/**
	 * Maps the given properties to the target object WITHOUT VALIDATING THE RESULT.
	 * If the properties could be set, this method returns TRUE, otherwise FALSE.
	 * Returning TRUE does not mean that the target object is valid and secure!
	 *
	 * Only use this method if you're sure that you don't need validation!
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param object $target The target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the properties could be mapped, otherwise FALSE
	 * @see mapAndValidate()
	 * @api
	 */
	public function map(array $propertyNames, $source, $target, $optionalPropertyNames = array()) {
		if (!is_object($source) && !is_array($source)) throw new Tx_Extbase_Property_Exception_InvalidSource('The source object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);

		if (is_string($target) && strpos($target, '_') !== FALSE) {
			return $this->transformToObject($source, $target, '--none--');
		}

		if (!is_object($target) && !is_array($target)) throw new Tx_Extbase_Property_Exception_InvalidTarget('The target object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);

		$this->mappingResults = new Tx_Extbase_Property_MappingResults();
		if (is_object($target)) {
			$targetClassSchema = $this->reflectionService->getClassSchema(get_class($target));
		} else {
			$targetClassSchema = NULL;
		}

		foreach ($propertyNames as $propertyName) {
			$propertyValue = NULL;
			if (is_array($source) || $source instanceof ArrayAccess) {
				if (isset($source[$propertyName])) {
					$propertyValue = $source[$propertyName];
				}
			} else {
				$propertyValue = Tx_Extbase_Reflection_ObjectAccess::getProperty($source, $propertyName);
			}

			if ($propertyValue === NULL && !in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError(new Tx_Extbase_Error_Error("Required property '$propertyName' does not exist." , 1236785359), $propertyName);
			} else {
				if ($targetClassSchema !== NULL && $targetClassSchema->hasProperty($propertyName)) {
					$propertyMetaData = $targetClassSchema->getProperty($propertyName);

					if (in_array($propertyMetaData['type'], array('array', 'ArrayObject', 'Tx_Extbase_Persistence_ObjectStorage')) && (strpos($propertyMetaData['elementType'], '_') !== FALSE || $propertyValue === '')) {
						$objects = array();
						if (is_array($propertyValue)) {
							foreach ($propertyValue as $value) {
								$objects[] = $this->transformToObject($value, $propertyMetaData['elementType'], $propertyName);
							}
						}

							// make sure we hand out what is expected
						if ($propertyMetaData['type'] === 'ArrayObject') {
							$propertyValue = new ArrayObject($objects);
						} elseif ($propertyMetaData['type'] === 'Tx_Extbase_Persistence_ObjectStorage') {
							$propertyValue = new Tx_Extbase_Persistence_ObjectStorage();
							foreach ($objects as $object) {
								$propertyValue->attach($object);
							}
						} else {
							$propertyValue = $objects;
						}
					} elseif ($propertyMetaData['type'] === 'DateTime' || strpos($propertyMetaData['type'], '_') !== FALSE) {
						$propertyValue = $this->transformToObject($propertyValue, $propertyMetaData['type'], $propertyName);
						if ($propertyValue === NULL) {
							continue;
						}
					}
				} elseif ($targetClassSchema !== NULL) {
					$this->mappingResults->addError(new Tx_Extbase_Error_Error("Property '$propertyName' does not exist in target class schema." , 1251813614), $propertyName);
				}

				if (is_array($target)) {
					$target[$propertyName] = $propertyValue;
				} elseif (Tx_Extbase_Reflection_ObjectAccess::setProperty($target, $propertyName, $propertyValue) === FALSE) {
					$this->mappingResults->addError(new Tx_Extbase_Error_Error("Property '$propertyName' could not be set." , 1236783102), $propertyName);
				}
			}
		}

		return !$this->mappingResults->hasErrors();
	}

	/**
	 * Transforms strings with UUIDs or arrays with UUIDs/identity properties
	 * into the requested type, if possible.
	 *
	 * @param mixed $propertyValue The value to transform, string or array
	 * @param string $targetType The type to transform to
	 * @param string $propertyName In case of an error we add this to the error message
	 * @return object The object, when no transformation was possible this may return NULL as well
	 */
	protected function transformToObject($propertyValue, $targetType, $propertyName) {
		if ($targetType === 'DateTime' || is_subclass_of($targetType, 'DateTime')) {
			// TODO replace this with converter implementation of FLOW3
			if ($propertyValue === '') {
				$propertyValue = NULL;
			} else {
				try {
					$propertyValue = new $targetType($propertyValue);
				} catch (Exception $e) {
					$propertyValue = NULL;
				}
			}
		} else {
			if (is_numeric($propertyValue)) {
				$propertyValue = $this->findObjectByUid($targetType, $propertyValue);
				if ($propertyValue === FALSE) {
					$this->mappingResults->addError(new Tx_Extbase_Error_Error('Querying the repository for the specified object with UUID ' . $propertyValue . ' was not successful.' , 1249379517), $propertyName);
				}
			} elseif (is_array($propertyValue)) {
				if (isset($propertyValue['__identity'])) {
					$existingObject = $this->findObjectByUid($targetType, $propertyValue['__identity']);
					if ($existingObject === FALSE) throw new Tx_Extbase_Property_Exception_TargetNotFound('Querying the repository for the specified object was not successful.', 1237305720);
					unset($propertyValue['__identity']);
					if (count($propertyValue) === 0) {
						$propertyValue = $existingObject;
					} elseif ($existingObject !== NULL) {
						$newObject = clone $existingObject;
						if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
							$propertyValue = $newObject;
						} else {
							$propertyValue = NULL;
						}
					}
				} else {
					$newObject = new $targetType;
					if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
						$propertyValue = $newObject;
					} else {
						$propertyValue = NULL;
					}
				}
			} else {
				throw new InvalidArgumentException('transformToObject() accepts only numeric values and arrays.', 1251814355);
			}
		}

		return $propertyValue;
	}

	/**
	 * Returns the results of the last mapping operation.
	 *
	 * @return Tx_Extbase_Property_MappingResults The mapping results (or NULL if no mapping has been carried out yet)
	 * @api
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}

	/**
	 * Finds an object from the repository by searching for its technical UID.
	 *
	 * @param string $dataType the data type to fetch
	 * @param int $uid The object's uid
	 * @return mixed Either the object matching the uid or, if none or more than one object was found, FALSE
	 */
	// TODO This is duplicated code; see Argument class
	protected function findObjectByUid($dataType, $uid) {
		$query = $this->queryFactory->create($dataType);
		$query->getQuerySettings()->setRespectSysLanguage(FALSE);
		$result = $query->matching($query->equals('uid', intval($uid)))->execute();
		$object = NULL;
		if (count($result) > 0) {
			$object = current($result);
		}
		return $object;
	}
}

?>