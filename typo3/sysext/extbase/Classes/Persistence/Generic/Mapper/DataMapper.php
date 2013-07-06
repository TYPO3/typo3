<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Mapper;

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
 * A mapper to map database tables configured in $TCA on domain objects.
 */
class DataMapper implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * A reference to the page select object providing methods to perform language and work space overlays
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageSelectObject;

	/**
	 * Cached data maps
	 *
	 * @var array
	 */
	protected $dataMaps = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
	 */
	protected $dataMapFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * The TYPO3 reference index object
	 *
	 * @var \TYPO3\CMS\Core\Database\ReferenceIndex
	 */
	protected $referenceIndex;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the identity map
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap $identityMap
	 * @return void
	 */
	public function injectIdentityMap(\TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $persistenceSession
	 * @return void
	 */
	public function injectSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the DataMap Factory
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory
	 * @return void
	 */
	public function injectDataMapFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory) {
		$this->dataMapFactory = $dataMapFactory;
	}

	/**
	 * Injects the Query Factory
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory
	 */
	public function injectQueryFactory(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Sets the query object model factory
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory
	 * @return void
	 */
	public function injectQomFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory) {
		$this->qomFactory = $qomFactory;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Maps the given rows on objects
	 *
	 * @param string $className The name of the class
	 * @param array $rows An array of arrays with field_name => value pairs
	 * @return array An array of objects of the given class
	 */
	public function map($className, array $rows) {
		$objects = array();
		foreach ($rows as $row) {
			$objects[] = $this->mapSingleRow($this->getTargetType($className, $row), $row);
		}
		return $objects;
	}

	/**
	 * Returns the target type for the given row.
	 *
	 * @param string $className The name of the class
	 * @param array $row A single array with field_name => value pairs
	 * @return string The target type (a class name)
	 */
	public function getTargetType($className, array $row) {
		$dataMap = $this->getDataMap($className);
		$targetType = $className;
		if ($dataMap->getRecordTypeColumnName() !== NULL) {
			foreach ($dataMap->getSubclasses() as $subclassName) {
				$recordSubtype = $this->getDataMap($subclassName)->getRecordType();
				if ($row[$dataMap->getRecordTypeColumnName()] === $recordSubtype) {
					$targetType = $subclassName;
					break;
				}
			}
		}
		return $targetType;
	}

	/**
	 * Maps a single row on an object of the given class
	 *
	 * @param string $className The name of the target class
	 * @param array $row A single array with field_name => value pairs
	 * @return object An object of the given class
	 */
	protected function mapSingleRow($className, array $row) {
		if ($this->identityMap->hasIdentifier($row['uid'], $className)) {
			$object = $this->identityMap->getObjectByIdentifier($row['uid'], $className);
		} else {
			$object = $this->createEmptyObject($className);
			$this->identityMap->registerObject($object, $row['uid']);
			$this->thawProperties($object, $row);
			$object->_memorizeCleanState();
			$this->persistenceSession->registerReconstitutedEntity($object);
		}
		return $object;
	}

	/**
	 * Creates a skeleton of the specified object
	 *
	 * @param string $className Name of the class to create a skeleton for
	 * @throws \TYPO3\CMS\Extbase\Object\Exception\CannotReconstituteObjectException
	 * @return object The object skeleton
	 */
	protected function createEmptyObject($className) {
		// Note: The class_implements() function also invokes autoload to assure that the interfaces
		// and the class are loaded. Would end up with __PHP_Incomplete_Class without it.
		if (!in_array('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface', class_implements($className))) {
			throw new \TYPO3\CMS\Extbase\Object\Exception\CannotReconstituteObjectException('Cannot create empty instance of the class "' . $className . '" because it does not implement the TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface.', 1234386924);
		}
		$object = $this->objectManager->getEmptyObject($className);
		return $object;
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to set properties on
	 * @param array $row
	 * @return void
	 */
	protected function thawProperties(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array $row) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$object->_setProperty('uid', intval($row['uid']));
		$object->_setProperty('pid', intval($row['pid']));
		$object->_setProperty('_localizedUid', intval($row['uid']));
		if ($dataMap->getLanguageIdColumnName() !== NULL) {
			$object->_setProperty('_languageUid', intval($row[$dataMap->getLanguageIdColumnName()]));
			if (isset($row['_LOCALIZED_UID'])) {
				$object->_setProperty('_localizedUid', intval($row['_LOCALIZED_UID']));
			}
		}
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName)) {
				continue;
			}
			$columnMap = $dataMap->getColumnMap($propertyName);
			$columnName = $columnMap->getColumnName();
			$propertyData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
			$propertyValue = NULL;
			if ($row[$columnName] !== NULL) {
				switch ($propertyData['type']) {
					case 'integer':
						$propertyValue = (integer) $row[$columnName];
						break;
					case 'float':
						$propertyValue = (double) $row[$columnName];
						break;
					case 'boolean':
						$propertyValue = (boolean) $row[$columnName];
						break;
					case 'string':
						$propertyValue = (string) $row[$columnName];
						break;
					case 'array':
						// $propertyValue = $this->mapArray($row[$columnName]); // Not supported, yet!
						break;
					case 'SplObjectStorage':
					case 'Tx_Extbase_Persistence_ObjectStorage':
					case 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage':
						$propertyValue = $this->mapResultToPropertyValue($object, $propertyName, $this->fetchRelated($object, $propertyName, $row[$columnName]));
						break;
					default:
						if ($propertyData['type'] === 'DateTime' || in_array('DateTime', class_parents($propertyData['type']))) {
							$propertyValue = $this->mapDateTime($row[$columnName], $columnMap->getDateTimeStorageFormat());
						} else {
							$propertyValue = $this->mapResultToPropertyValue($object, $propertyName, $this->fetchRelated($object, $propertyName, $row[$columnName]));
						}
						break;
				}
			}
			if ($propertyValue !== NULL) {
				$object->_setProperty($propertyName, $propertyValue);
			}
		}
	}

	/**
	 * Creates a DateTime from an unix timestamp or date/datetime value.
	 * If the input is empty, NULL is returned.
	 *
	 * @param integer|string $value Unix timestamp or date/datetime value
	 * @param NULL|string $storageFormat Storage format for native date/datetime fields
	 * @return \DateTime
	 */
	protected function mapDateTime($value, $storageFormat = NULL) {
		if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			// 0 -> NULL !!!
			return NULL;
		} elseif ($storageFormat === 'date' || $storageFormat === 'datetime') {
			// native date/datetime values are stored in UTC
			$utcTimeZone = new \DateTimeZone('UTC');
			$utcDateTime = new \DateTime($value, $utcTimeZone);
			$currentTimeZone = new \DateTimeZone(date_default_timezone_get());
			return $utcDateTime->setTimezone($currentTimeZone);
		} else {
			return new \DateTime(date('c', $value));
		}
	}

	/**
	 * Fetches a collection of objects related to a property of a parent object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @param boolean $enableLazyLoading A flag indication if the related objects should be lazy loaded
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface The result
	 */
	public function fetchRelated(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $fieldValue = '', $enableLazyLoading = TRUE) {
		$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
		if ($enableLazyLoading === TRUE && $propertyMetaData['lazy']) {
			if (in_array($propertyMetaData['type'], array('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage'), TRUE)) {
				$result = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyObjectStorage', $parentObject, $propertyName, $fieldValue);
			} else {
				if (empty($fieldValue)) {
					$result = NULL;
				} else {
					$result = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyLoadingProxy', $parentObject, $propertyName, $fieldValue);
				}
			}
		} else {
			$result = $this->fetchRelatedEager($parentObject, $propertyName, $fieldValue);
		}
		return $result;
	}

	/**
	 * Fetches the related objects from the storage backend.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @return mixed
	 */
	protected function fetchRelatedEager(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $fieldValue = '') {
		return $fieldValue === '' ? $this->getEmptyRelationValue($parentObject, $propertyName) : $this->getNonEmptyRelationValue($parentObject, $propertyName, $fieldValue);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $propertyName
	 * @return array|NULL
	 */
	protected function getEmptyRelationValue(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$relatesToOne = $columnMap->getTypeOfRelation() == \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_ONE;
		return $relatesToOne ? NULL : array();
	}

	/**
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $propertyName
	 * @param string $fieldValue
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	protected function getNonEmptyRelationValue(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $fieldValue) {
		$query = $this->getPreparedQuery($parentObject, $propertyName, $fieldValue);
		return $query->execute();
	}

	/**
	 * Builds and returns the prepared query, ready to be executed.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $propertyName
	 * @param string $fieldValue
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	protected function getPreparedQuery(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $fieldValue = '') {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$type = $this->getType(get_class($parentObject), $propertyName);
		$query = $this->queryFactory->create($type);
		$query->getQuerySettings()->setRespectStoragePage(FALSE);
		$query->getQuerySettings()->setRespectSysLanguage(FALSE);
		if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			if ($columnMap->getChildSortByFieldName() !== NULL) {
				$query->setOrderings(array($columnMap->getChildSortByFieldName() => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING));
			}
		} elseif ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$query->setSource($this->getSource($parentObject, $propertyName));
			if ($columnMap->getChildSortByFieldName() !== NULL) {
				$query->setOrderings(array($columnMap->getChildSortByFieldName() => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING));
			}
		}
		$query->matching($this->getConstraint($query, $parentObject, $propertyName, $fieldValue, $columnMap->getRelationTableMatchFields()));
		return $query;
	}

	/**
	 * Builds and returns the constraint for multi value properties.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $propertyName
	 * @param string $fieldValue
	 * @param array $relationTableMatchFields
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint
	 */
	protected function getConstraint(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $fieldValue = '', $relationTableMatchFields = array()) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		if ($columnMap->getParentKeyFieldName() !== NULL) {
			$constraint = $query->equals($columnMap->getParentKeyFieldName(), $parentObject);
			if ($columnMap->getParentTableFieldName() !== NULL) {
				$constraint = $query->logicalAnd($constraint, $query->equals($columnMap->getParentTableFieldName(), $this->getDataMap(get_class($parentObject))->getTableName()));
			}
		} else {
			$constraint = $query->in('uid', \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $fieldValue));
		}
		if (count($relationTableMatchFields) > 0) {
			foreach ($relationTableMatchFields as $relationTableMatchFieldName => $relationTableMatchFieldValue) {
				$constraint = $query->logicalAnd($constraint, $query->equals($relationTableMatchFieldName, $relationTableMatchFieldValue));
			}
		}
		return $constraint;
	}

	/**
	 * Builds and returns the source to build a join for a m:n relation.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $propertyName
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source
	 */
	protected function getSource(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$left = $this->qomFactory->selector(NULL, $columnMap->getRelationTableName());
		$childClassName = $this->getType(get_class($parentObject), $propertyName);
		$right = $this->qomFactory->selector($childClassName, $columnMap->getChildTableName());
		$joinCondition = $this->qomFactory->equiJoinCondition($columnMap->getRelationTableName(), $columnMap->getChildKeyFieldName(), $columnMap->getChildTableName(), 'uid');
		$source = $this->qomFactory->join($left, $right, \TYPO3\CMS\Extbase\Persistence\Generic\Query::JCR_JOIN_TYPE_INNER, $joinCondition);
		return $source;
	}

	/**
	 * Returns the given result as property value of the specified property type.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $propertyName
	 * @param mixed $result The result
	 * @return mixed
	 */
	public function mapResultToPropertyValue(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $result) {
		if ($result instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface) {
			$propertyValue = $result;
		} else {
			$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
			if (in_array($propertyMetaData['type'], array('array', 'ArrayObject', 'SplObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage', 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage'), TRUE)) {
				$objects = array();
				foreach ($result as $value) {
					$objects[] = $value;
				}
				if ($propertyMetaData['type'] === 'ArrayObject') {
					$propertyValue = new \ArrayObject($objects);
				} elseif (in_array($propertyMetaData['type'], array('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage'), TRUE)) {
					$propertyValue = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
					foreach ($objects as $object) {
						$propertyValue->attach($object);
					}
					$propertyValue->_memorizeCleanState();
				} else {
					$propertyValue = $objects;
				}
			} elseif (strpbrk($propertyMetaData['type'], '_\\') !== FALSE) {
				if (is_object($result) && $result instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface) {
					$propertyValue = $result->getFirst();
				} else {
					$propertyValue = $result;
				}
			}
		}
		return $propertyValue;
	}

	/**
	 * Counts the number of related objects assigned to a property of a parent object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @return integer
	 */
	public function countRelated(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $fieldValue = '') {
		$query = $this->getPreparedQuery($parentObject, $propertyName, $fieldValue);
		return $query->execute()->count();
	}

	/**
	 * Delegates the call to the Data Map.
	 * Returns TRUE if the property is persistable (configured in $TCA)
	 *
	 * @param string $className The property name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property is persistable (configured in $TCA)
	 */
	public function isPersistableProperty($className, $propertyName) {
		$dataMap = $this->getDataMap($className);
		return $dataMap->isPersistableProperty($propertyName);
	}

	/**
	 * Returns a data map for a given class name
	 *
	 * @param string $className The class name you want to fetch the Data Map for
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap The data map
	 */
	public function getDataMap($className) {
		if (!is_string($className) || strlen($className) === 0) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('No class name was given to retrieve the Data Map for.', 1251315965);
		}
		if (!isset($this->dataMaps[$className])) {
			$this->dataMaps[$className] = $this->dataMapFactory->buildDataMap($className);
		}
		return $this->dataMaps[$className];
	}

	/**
	 * Returns the selector (table) name for a given class name.
	 *
	 * @param string $className
	 * @return string The selector name
	 */
	public function convertClassNameToTableName($className = NULL) {
		if ($className !== NULL) {
			$tableName = $this->getDataMap($className)->getTableName();
		} else {
			$tableName = strtolower($className);
		}
		return $tableName;
	}

	/**
	 * Returns the column name for a given property name of the specified class.
	 *
	 * @param string $propertyName
	 * @param string $className
	 * @return string The column name
	 */
	public function convertPropertyNameToColumnName($propertyName, $className = NULL) {
		if (!empty($className)) {
			$dataMap = $this->getDataMap($className);
			if ($dataMap !== NULL) {
				$columnMap = $dataMap->getColumnMap($propertyName);
				if ($columnMap !== NULL) {
					return $columnMap->getColumnName();
				}
			}
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
	}

	/**
	 * Returns the type of a child object.
	 *
	 * @param string $parentClassName The class name of the object this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
	 * @return string The class name of the child object
	 */
	public function getType($parentClassName, $propertyName) {
		$propertyMetaData = $this->reflectionService->getClassSchema($parentClassName)->getProperty($propertyName);
		if (!empty($propertyMetaData['elementType'])) {
			$type = $propertyMetaData['elementType'];
		} elseif (!empty($propertyMetaData['type'])) {
			$type = $propertyMetaData['type'];
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException('Could not determine the child object type.', 1251315967);
		}
		return $type;
	}
}

?>