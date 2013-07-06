<?php
namespace TYPO3\CMS\Extbase\Property;

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
 * array(
 * 'someProperty' => 'SomeValue'
 * )
 * );
 * $mapper->mapAndValidate(array('someProperty'), $source, $target);
 *
 * Now the target object equals the source object.
 *
 * @api
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
class Mapper implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Results of the last mapping operation
	 *
	 * @var \TYPO3\CMS\Extbase\Property\MappingResults
	 */
	protected $mappingResults;

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @param \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 */
	public function injectValidatorResolver(\TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
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
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @param \TYPO3\CMS\Extbase\Validation\Validator\ObjectValidatorInterface $targetObjectValidator A validator used for validating the target object
	 *
	 * @return boolean TRUE if the mapped properties are valid, otherwise FALSE
	 * @see getMappingResults()
	 * @see map()
	 * @api
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), \TYPO3\CMS\Extbase\Validation\Validator\ObjectValidatorInterface $targetObjectValidator) {
		$backupProperties = array();
		$this->map($propertyNames, $source, $backupProperties, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) {
			return FALSE;
		}
		$this->map($propertyNames, $source, $target, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) {
			return FALSE;
		}
		if ($targetObjectValidator->isValid($target) !== TRUE) {
			$this->addErrorsFromObjectValidator($targetObjectValidator->getErrors());
			$backupMappingResult = $this->mappingResults;
			$this->map($propertyNames, $backupProperties, $source, $optionalPropertyNames);
			$this->mappingResults = $backupMappingResult;
		}
		return !$this->mappingResults->hasErrors();
	}

	/**
	 * Add errors to the mapping result from an object validator (property errors).
	 *
	 * @param array $errors Array of \TYPO3\CMS\Extbase\Validation\PropertyError
	 * @return void
	 */
	protected function addErrorsFromObjectValidator($errors) {
		foreach ($errors as $error) {
			if ($error instanceof \TYPO3\CMS\Extbase\Validation\PropertyError) {
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
	 * @param object|array|string $target The target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @throws Exception\InvalidSourceException
	 * @throws Exception\InvalidTargetException
	 * @return boolean TRUE if the properties could be mapped, otherwise FALSE, or the object in case it could be resolved
	 * @see mapAndValidate()
	 * @api
	 */
	public function map(array $propertyNames, $source, $target, $optionalPropertyNames = array()) {
		if (!is_object($source) && !is_array($source)) {
			throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException('The source object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);
		}
		if (is_string($target) && strpbrk($target, '_\\') !== FALSE) {
			return $this->transformToObject($source, $target, '--none--');
		}
		if (!is_object($target) && !is_array($target)) {
			throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('The target object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);
		}
		$this->mappingResults = new \TYPO3\CMS\Extbase\Property\MappingResults();
		if (is_object($target)) {
			$targetClassSchema = $this->reflectionService->getClassSchema(get_class($target));
		} else {
			$targetClassSchema = NULL;
		}
		foreach ($propertyNames as $propertyName) {
			$propertyValue = NULL;
			if (is_array($source) || $source instanceof \ArrayAccess) {
				if (isset($source[$propertyName])) {
					$propertyValue = $source[$propertyName];
				}
			} else {
				$propertyValue = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($source, $propertyName);
			}
			if ($propertyValue === NULL && !in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError(new \TYPO3\CMS\Extbase\Error\Error("Required property '{$propertyName}' does not exist.", 1236785359), $propertyName);
			} else {
				if ($targetClassSchema !== NULL && $targetClassSchema->hasProperty($propertyName)) {
					$propertyMetaData = $targetClassSchema->getProperty($propertyName);
					if (in_array($propertyMetaData['type'], array('array', 'ArrayObject', 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage'), TRUE) && (strpbrk($propertyMetaData['elementType'], '_\\') !== FALSE || $propertyValue === '')) {
						$objects = array();
						if (is_array($propertyValue)) {
							foreach ($propertyValue as $value) {
								$transformedObject = $this->transformToObject($value, $propertyMetaData['elementType'], $propertyName);
								if ($transformedObject !== NULL) {
									$objects[] = $transformedObject;
								}
							}
						}
						// make sure we hand out what is expected
						if ($propertyMetaData['type'] === 'ArrayObject') {
							$propertyValue = new \ArrayObject($objects);
						} elseif (in_array($propertyMetaData['type'], array('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage'), TRUE)) {
							$propertyValue = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
							foreach ($objects as $object) {
								$propertyValue->attach($object);
							}
						} else {
							$propertyValue = $objects;
						}
					} elseif ($propertyMetaData['type'] === 'DateTime' || strpbrk($propertyMetaData['type'], '_\\') !== FALSE) {
						$propertyValue = $this->transformToObject($propertyValue, $propertyMetaData['type'], $propertyName);
						if ($propertyValue === NULL) {
							continue;
						}
					}
				} elseif (
					$targetClassSchema !== NULL
					&& is_subclass_of($target, 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity')
					&& is_subclass_of($target, 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractValueObject')
				) {
					$this->mappingResults->addError(new \TYPO3\CMS\Extbase\Error\Error("Property '{$propertyName}' does not exist in target class schema.", 1251813614), $propertyName);
				}
				if (is_array($target)) {
					$target[$propertyName] = $propertyValue;
				} elseif (\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($target, $propertyName, $propertyValue) === FALSE) {
					$this->mappingResults->addError(new \TYPO3\CMS\Extbase\Error\Error("Property '{$propertyName}' could not be set.", 1236783102), $propertyName);
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
	 * @throws Exception\InvalidTargetException
	 * @throws \InvalidArgumentException
	 * @return object The object, when no transformation was possible this may return NULL as well
	 */
	protected function transformToObject($propertyValue, $targetType, $propertyName) {
		if ($targetType === 'DateTime' || is_subclass_of($targetType, 'DateTime')) {
			if ($propertyValue === '') {
				$propertyValue = NULL;
			} else {
				try {
					$propertyValue = $this->objectManager->get($targetType, $propertyValue);
				} catch (\Exception $e) {
					$propertyValue = NULL;
				}
			}
		} else {
			if (ctype_digit((string)$propertyValue)) {
				$propertyValue = $this->findObjectByUid($targetType, $propertyValue);
				if ($propertyValue === FALSE) {
					$this->mappingResults->addError(new \TYPO3\CMS\Extbase\Error\Error('Querying the repository for the specified object with UUID ' . $propertyValue . ' was not successful.', 1249379517), $propertyName);
				}
			} elseif (is_array($propertyValue)) {
				if (isset($propertyValue['__identity'])) {
					$existingObject = $this->findObjectByUid($targetType, $propertyValue['__identity']);
					if ($existingObject === FALSE) {
						throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Querying the repository for the specified object was not successful.', 1237305720);
					}
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
					$newObject = $this->objectManager->get($targetType);
					if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
						$propertyValue = $newObject;
					} else {
						$propertyValue = NULL;
					}
				}
			} else {
				throw new \InvalidArgumentException('transformToObject() accepts only numeric values and arrays.', 1251814355);
			}
		}
		return $propertyValue;
	}

	/**
	 * Returns the results of the last mapping operation.
	 *
	 * @return \TYPO3\CMS\Extbase\Property\MappingResults The mapping results (or NULL if no mapping has been carried out yet)
	 * @api
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}

	/**
	 * Finds an object from the repository by searching for its technical UID.
	 * TODO This is duplicated code; see Argument class
	 *
	 * @param string $dataType the data type to fetch
	 * @param integer $uid The object's uid
	 * @return object Either the object matching the uid or, if none or more than one object was found, NULL
	 */
	protected function findObjectByUid($dataType, $uid) {
		$query = $this->queryFactory->create($dataType);
		$query->getQuerySettings()->setRespectSysLanguage(FALSE);
		$query->getQuerySettings()->setRespectStoragePage(FALSE);
		return $query->matching($query->equals('uid', intval($uid)))->execute()->getFirst();
	}
}

?>