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
 * A mapper to map database tables configured in $TCA on domain objects.
 *
 * @package Extbase
 * @subpackage Persistence\Mapper
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Mapper_DataMapper implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_Persistence_QOM_QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var Tx_Extbase_Persistence_Session
	 */
	protected $persistenceSession;

	/**
	 * A reference to the page select object providing methods to perform language and work space overlays
	 *
	 * @var t3lib_pageSelect
	 **/
	protected $pageSelectObject;

	/**
	 * Cached data maps
	 *
	 * @var array
	 **/
	protected $dataMaps = array();

	/**
	 * @var Tx_Extbase_Persistence_Mapper_DataMapFactory
	 */
	protected $dataMapFactory;
	
	/**
	 * @var Tx_Extbase_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * The TYPO3 reference index object
	 *
	 * @var t3lib_refindex
	 **/
	protected $referenceIndex;

	/**
	 * Constructs a new mapper
	 *
	 */
	public function __construct() {
		$this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
	}

	/**
	 * Injects the identity map
	 *
	 * @param Tx_Extbase_Persistence_IdentityMap $identityMap
	 * @return void
	 */
	public function injectIdentityMap(Tx_Extbase_Persistence_IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param Tx_Extbase_Persistence_Session $persistenceSession
	 * @return void
	 */
	public function injectSession(Tx_Extbase_Persistence_Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
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
	 * Injects the DataMap Factory
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMapFactory
	 * @return void
	 */
	public function injectDataMapFactory(Tx_Extbase_Persistence_Mapper_DataMapFactory $dataMapFactory) {
		$this->dataMapFactory = $dataMapFactory;
	}
	
	/**
	 * Sets the query object model factory
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelFactory $qomFactory
	 * @return void
	 */
	public function setQomFactory(Tx_Extbase_Persistence_QOM_QueryObjectModelFactory $qomFactory) {
		$this->qomFactory = $qomFactory;
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
					$targetType =  $subclassName;
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
			$this->persistenceSession->registerReconstitutedObject($object);
		}
		return $object;
	}

	/**
	 * Creates a skeleton of the specified object
	 *
	 * @param string $className Name of the class to create a skeleton for
	 * @return object The object skeleton
	 */
	protected function createEmptyObject($className) {
		// Note: The class_implements() function also invokes autoload to assure that the interfaces
		// and the class are loaded. Would end up with __PHP_Incomplete_Class without it.
		if (!in_array('Tx_Extbase_DomainObject_DomainObjectInterface', class_implements($className))) throw new Tx_Extbase_Object_Exception_CannotReconstituteObject('Cannot create empty instance of the class "' . $className . '" because it does not implement the Tx_Extbase_DomainObject_DomainObjectInterface.', 1234386924);
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		return $object;
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to set properties on
	 * @param array $row
	 * @return void
	 */
	protected function thawProperties(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $row) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$object->_setProperty('uid', intval($row['uid']));
		$object->_setProperty('_localizedUid', intval($row['uid']));
		if ($dataMap->getLanguageIdColumnName() !== NULL) {
			$object->_setProperty('_languageUid', intval($row[$dataMap->getLanguageIdColumnName()]));
			if (isset($row['_LOCALIZED_UID'])) {
				$object->_setProperty('_localizedUid', intval($row['_LOCALIZED_UID']));
			}
		}
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName)) continue;
			$columnMap = $dataMap->getColumnMap($propertyName);
			$columnName = $columnMap->getColumnName();
			$propertyData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);			
			$propertyValue = NULL;
			if ($row[$columnName] !== NULL) {
				switch ($propertyData['type']) {
					case 'integer':
						$propertyValue = (int) $row[$columnName];
					break;
					case 'float':
						$propertyValue = (float) $row[$columnName];
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
						$propertyValue = $this->mapResultToPropertyValue($object, $propertyName, $this->fetchRelated($object, $propertyName, $row[$columnName]));
					break;
					default:
						if (($propertyData['type'] === 'DateTime') || in_array('DateTime', class_parents($propertyData['type']))) {
							$propertyValue = $this->mapDateTime($row[$columnName]);
						} else {
							$propertyValue = $this->mapResultToPropertyValue($object, $propertyName, $this->fetchRelated($object, $propertyName, $row[$columnName]));
							// $propertyValue = $this->mapToObject($row[$columnName]); // Not supported, yet!
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
	 * Creates a DateTime from an unix timestamp. If the input is empty
	 * NULL is returned.
	 *
	 * @param integer $timestamp
	 * @return DateTime
	 */
	protected function mapDateTime($timestamp) {
		if (empty($timestamp)) { // 0 -> NULL !!!
			return NULL;
		} else {
			return new DateTime(date('c', $timestamp));
		}
	}

	/**
	 * Fetches a collection of objects related to a property of a parent object
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @param bool $enableLazyLoading A flag indication if the related objects should be lazy loaded
	 * @param bool $performLanguageOverlay A flag indication if the related objects should be localized
	 * @return mixed The result
	 */
	public function fetchRelated(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $fieldValue = '', $enableLazyLoading = TRUE) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
		if ($enableLazyLoading === TRUE && $propertyMetaData['lazy']) {
			if ($propertyMetaData['type'] === 'Tx_Extbase_Persistence_ObjectStorage') {
				$result = t3lib_div::makeInstance('Tx_Extbase_Persistence_LazyObjectStorage', $parentObject, $propertyName, $fieldValue);				
			} else {
				if (empty($fieldValue)) {
					$result = NULL;
				} else {
					$result = t3lib_div::makeInstance('Tx_Extbase_Persistence_LazyLoadingProxy', $parentObject, $propertyName, $fieldValue);
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
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @param bool $performLanguageOverlay A flag indication if the related objects should be localized
	 * @return void
	 */
	protected function fetchRelatedEager(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $fieldValue = '') {
		if ($fieldValue === '') return array();
		$query = $this->getPreparedQuery($parentObject, $propertyName, $fieldValue);
		return $query->execute();
	}
	
	/**
	 * Builds and returns the prepared query, ready to be executed.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject 
	 * @param string $propertyName 
	 * @param string $fieldValue 
	 * @return void
	 */
	protected function getPreparedQuery(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $fieldValue = '') {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
		$type = $this->getType(get_class($parentObject), $propertyName);
		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
			$query = $queryFactory->create($type);
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$query = $queryFactory->create($type);
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			if ($columnMap->getChildSortByFieldName() !== NULL) {
				$query->setOrderings(array($columnMap->getChildSortByFieldName() => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING));
			}
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$query = $queryFactory->create($type);
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			$query->setSource($this->getSource($parentObject, $propertyName));
			if ($columnMap->getChildSortByFieldName() !== NULL) {
				$query->setOrderings(array($columnMap->getChildSortByFieldName() => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING));
			}
		} else {
			throw new Tx_Extbase_Persistence_Exception('Could not determine type of relation for the property "' . $propertyName . '". This is mainly caused by a missing type declaration above the property definition. If the PHPDoc comment is there, make sure it starts with "/**" and not just with "/*".', 1252502725);
		}
		$query->matching($this->getConstraint($query, $parentObject, $propertyName, $fieldValue, $columnMap->getRelationTableMatchFields()));
		return $query;
	}
	
	/**
	 * Builds and returns the constraint for multi value properties.
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject 
	 * @param string $propertyName 
	 * @param string $fieldValue 
	 * @param array $relationTableMatchFields 
	 * @return Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint
	 */
	protected function getConstraint(Tx_Extbase_Persistence_QueryInterface $query, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $fieldValue = '', $relationTableMatchFields = array()) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		if ($columnMap->getParentKeyFieldName() !== NULL) {
			$constraint = $query->equals($columnMap->getParentKeyFieldName(), $parentObject);
 			if($columnMap->getParentTableFieldName() !== NULL) {
 				$constraint = $query->logicalAnd(
					$constraint,
					$query->equals($columnMap->getParentTableFieldName(), $this->getDataMap(get_class($parentObject))->getTableName())
					);
 			}
		} else {
			$constraint = $query->in('uid', t3lib_div::intExplode(',', $fieldValue));
		}
		if (count($relationTableMatchFields) > 0) {
			foreach($relationTableMatchFields as $relationTableMatchFieldName => $relationTableMatchFieldValue) {
				$constraint = $query->logicalAnd($constraint, $query->equals($relationTableMatchFieldName, $relationTableMatchFieldValue));
			}
		}
		return $constraint;
	}

	/**
	 * Builds and returns the source to build a join for a m:n relation.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject 
	 * @param string $propertyName 
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface $source
	 */
	protected function getSource(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$left = $this->qomFactory->selector(NULL, $columnMap->getRelationTableName());
		$childClassName = $this->getType(get_class($parentObject), $propertyName);
		$right = $this->qomFactory->selector($childClassName, $columnMap->getChildTableName());
		$joinCondition = $this->qomFactory->equiJoinCondition($columnMap->getRelationTableName(), $columnMap->getChildKeyFieldName(), $columnMap->getChildTableName(), 'uid');
		$source = $this->qomFactory->join(
			$left,
			$right,
			Tx_Extbase_Persistence_QueryInterface::JCR_JOIN_TYPE_INNER,
			$joinCondition
			);
		return $source;
	}
	
	/**
	 * Returns the given result as property value of the specified property type.
	 *
	 * @param mixed $result The result could be an object or an ObjectStorage 
	 * @param array $propertyMetaData The property meta data
	 * @param array $result The result 
	 * @return void
	 */
	public function mapResultToPropertyValue(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $result) {
		if ($result instanceof Tx_Extbase_Persistence_LoadingStrategyInterface) {
			$propertyValue = $result;
		} else {
			$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
			$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
			if (in_array($propertyMetaData['type'], array('array', 'ArrayObject', 'SplObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage'))) {
				$elementType = $this->getType(get_class($parentObject), $propertyName);
				$objects = array();
				foreach ($result as $value) {
					$objects[] = $value;
				}

				if ($propertyMetaData['type'] === 'ArrayObject') {
					$propertyValue = new ArrayObject($objects);
				} elseif ($propertyMetaData['type'] === 'Tx_Extbase_Persistence_ObjectStorage') {
					$propertyValue = new Tx_Extbase_Persistence_ObjectStorage();
					foreach ($objects as $object) {
						$propertyValue->attach($object);
					}
					$propertyValue->_memorizeCleanState();
				} else {
					$propertyValue = $objects;
				}
			} elseif (strpos($propertyMetaData['type'], '_') !== FALSE) {
				if (is_array($result)) {
					$propertyValue = current($result);
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
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 */
	public function countRelated(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $fieldValue = '') {
		$query = $this->getPreparedQuery($parentObject, $propertyName, $fieldValue);
		return $query->count();
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
	 * @return Tx_Extbase_Persistence_Mapper_DataMap The data map
	 */
	public function getDataMap($className) {
		if (!is_string($className) || strlen($className) === 0) throw new Tx_Extbase_Persistence_Exception('No class name was given to retrieve the Data Map for.', 1251315965);
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
	 * @param string $className
	 * @param string $propertyName
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
		return Tx_Extbase_Utility_Extension::convertCamelCaseToLowerCaseUnderscored($propertyName);
	}
	
	/**
	 * Returns the type of a child object.
	 *
	 * @param string $parentClassName The class name of the object this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @return string The class name of the child object
	 */
	public function getType($parentClassName, $propertyName) {
		$propertyMetaData = $this->reflectionService->getClassSchema($parentClassName)->getProperty($propertyName);
		if (!empty($propertyMetaData['elementType'])) {
			$type = $propertyMetaData['elementType'];
		} elseif (!empty($propertyMetaData['type'])) {
			$type = $propertyMetaData['type'];
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('Could not determine the child object type.', 1251315967);
		}
		return $type;
	}

}
?>