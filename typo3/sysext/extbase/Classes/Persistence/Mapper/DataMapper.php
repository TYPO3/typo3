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
	protected $QOMFactory;

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
	 * Injects the persistence manager
	 *
	 * @param Tx_Extbase_Persistence_ManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(Tx_Extbase_Persistence_ManagerInterface $persistenceManager) {
		$this->QOMFactory = $persistenceManager->getBackend()->getQOMFactory();
		$this->persistenceSession = $persistenceManager->getSession();
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
	 * Maps the (aggregate root) rows and registers them as reconstituted
	 * with the session.
	 *
	 * @param Tx_Extbase_Persistence_RowIteratorInterface $rows
	 * @return array
	 */
	public function map($className, Tx_Extbase_Persistence_RowIteratorInterface $rows) {
		$objects = array();
		foreach ($rows as $row) {
			$objects[] = $this->mapSingleRow($className, $row);
		}
		return $objects;
	}

	/**
	 * Maps a single node into the object it represents
	 *
	 * @param Tx_Extbase_Persistence_RowInterface $node
	 * @return object
	 */
	protected function mapSingleRow($className, Tx_Extbase_Persistence_RowInterface $row) {
		if ($this->identityMap->hasIdentifier($row->getValue('uid'), $className)) {
			$object = $this->identityMap->getObjectByIdentifier($row->getValue('uid'), $className);
		} else {
			$object = $this->createEmptyObject($className);
			$this->thawProperties($object, $row);
			$this->identityMap->registerObject($object, $object->getUid());
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
	 * @param Tx_Extbase_Persistence_RowInterface $row
	 * @return void
	 */
	protected function thawProperties(Tx_Extbase_DomainObject_DomainObjectInterface $object, Tx_Extbase_Persistence_RowInterface $row) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$properties = $object->_getProperties();
		$object->_setProperty('uid', $row->getValue('uid'));
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName)) continue;
			$columnMap = $dataMap->getColumnMap($propertyName);
			$columnName = $columnMap->getColumnName();
			$propertyValue = NULL;
			$propertyType = $columnMap->getPropertyType();
			switch ($propertyType) {
				case Tx_Extbase_Persistence_PropertyType::STRING;
				case Tx_Extbase_Persistence_PropertyType::DATE;
				case Tx_Extbase_Persistence_PropertyType::LONG;
				case Tx_Extbase_Persistence_PropertyType::DOUBLE;
				case Tx_Extbase_Persistence_PropertyType::BOOLEAN;
				if ($row->hasValue($columnName)) {
					$rawPropertyValue = $row->getValue($columnName);
					$propertyValue = $dataMap->convertFieldValueToPropertyValue($propertyType, $rawPropertyValue);
				}
				break;
				case (Tx_Extbase_Persistence_PropertyType::REFERENCE):
					if (is_null($row->getValue($columnName))) {
						$propertyValue = NULL;
					} else {
						$fieldValue = $row->getValue($columnMap->getColumnName());
						$result = $this->fetchRelated($object, $propertyName, $fieldValue);
						$propertyValue = $this->mapResultToPropertyValue($object, $propertyName, $result);
					}
					break;
				default:
					// FIXME throw exception
					break;
			}
			$object->_setProperty($propertyName, $propertyValue);
		}
	}

	/**
	 * Fetches a collection of objects related to a property of a parent object
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The corresponding Data Map of the property
	 * @return mixed The result
	 */
	public function fetchRelated(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName, $fieldValue = '', $enableLazyLoading = TRUE) {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
		if ($enableLazyLoading === TRUE && ($propertyMetaData['lazy'] || ($columnMap->getLoadingStrategy() !== Tx_Extbase_Persistence_Mapper_ColumnMap::STRATEGY_EAGER))) {
			if (($propertyMetaData['type'] === 'Tx_Extbase_Persistence_ObjectStorage') || ($columnMap->getLoadingStrategy() === Tx_Extbase_Persistence_Mapper_ColumnMap::STRATEGY_LAZY_STORAGE)) {
				$result = t3lib_div::makeInstance('Tx_Extbase_Persistence_LazyObjectStorage', $parentObject, $propertyName, $fieldValue);				
			} else {
				$result = t3lib_div::makeInstance('Tx_Extbase_Persistence_LazyLoadingProxy', $parentObject, $propertyName, $fieldValue);
			}
		} else {
			$result = $this->fetchRelatedEager($parentObject, $propertyName, $fieldValue);
		}
		return $result;
	}
	
	/**
	 * Fetches the related objects from the storage backend.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @return void
	 */
	protected function fetchRelatedEager(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName, $fieldValue = '') {
		if ($fieldValue === '') return array();
		$query = $this->getPreparedQuery($parentObject, $propertyName, $fieldValue);
		return $query->execute();
	}
	
	/**
	 * Builds and returns the prepared query, ready to be executed.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject 
	 * @param string $propertyName 
	 * @param string $fieldValue 
	 * @return void
	 */
	protected function getPreparedQuery(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName, $fieldValue = '') {
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
		$parentKeyFieldName = $columnMap->getParentKeyFieldName();
		$childSortByFieldName = $columnMap->getChildSortByFieldName();
		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
			$query = $queryFactory->create($this->getType($parentObject, $propertyName));
			$query->matching($query->withUid(intval($fieldValue)));
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$query = $queryFactory->create($this->getElementType($parentObject, $propertyName));
			// TODO: This is an ugly hack, just ignoring the storage page state from here. Actually, the query settings would have to be passed into the DataMapper, so we can respect
			// enableFields and storage page settings.
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			if (!empty($childSortByFieldName)) {
				$query->setOrderings(array($childSortByFieldName => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING));
			}
			if (isset($parentKeyFieldName)) {
				$query->matching($query->equals($parentKeyFieldName, $parentObject->getUid()));
			} else {
				$query->matching($query->equals('uid', t3lib_div::intExplode(',', $fieldValue)));					
			}
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$query = $queryFactory->create($this->getElementType($parentObject, $propertyName));
			// TODO: This is an ugly hack, just ignoring the storage page state from here. Actually, the query settings would have to be passed into the DataMapper, so we can respect
			// enableFields and storage page settings.
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			$relationTableName = $columnMap->getRelationTableName();
			$left = $this->QOMFactory->selector(NULL, $relationTableName);
			$childClassName = $this->getElementType($parentObject, $propertyName);
			$childTableName = $columnMap->getChildTableName();
			$right = $this->QOMFactory->selector($childClassName, $childTableName);
			$joinCondition = $this->QOMFactory->equiJoinCondition($relationTableName, $columnMap->getChildKeyFieldName(), $childTableName, 'uid');
			$source = $this->QOMFactory->join(
				$left,
				$right,
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER,
				$joinCondition
				);
			$query->setSource($source);
			if (!empty($childSortByFieldName)) {
				$query->setOrderings(array($relationTableName . '.' . $childSortByFieldName => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING));
			}
			$query->matching($query->equals($parentKeyFieldName, $parentObject->getUid()));
		} else {
			throw new Tx_Extbase_Persistence_Exception('Could not determine type of relation.', 1252502725);
		}
		return $query;
	}

	/**
	 * Returns the given result as property value of the specified property type.
	 *
	 * @param mixed $result The result could be an object or an ObjectStorage 
	 * @param array $propertyMetaData The property meta data
	 * @return void
	 */
	public function mapResultToPropertyValue(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName, $result) {
		if ($result instanceof Tx_Extbase_Persistence_LoadingStrategyInterface) {
			$propertyValue = $result;
		} else {
			$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
			$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
			if (in_array($propertyMetaData['type'], array('array', 'ArrayObject', 'Tx_Extbase_Persistence_ObjectStorage'))) {
				$elementType = $this->getElementType($parentObject, $propertyName);
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
				} else {
					$propertyValue = $objects;
				}
			} elseif (strpos($propertyMetaData['type'], '_') !== FALSE) {
				$propertyValue = current($result);
			}
		}
		return $propertyValue;
	}
	
	/**
	 * Counts the number of related objects assigned to a property of a parent object
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 */
	public function countRelated(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName, $fieldValue = '') {
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
			// FIXME This is too expensive for table name aliases -> implement a DataMapBuilder (knowing the aliases defined in $TCA)
			$columnMapping = array();
			$tableName = '';
			$extbaseSettings = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
			if (is_array($extbaseSettings['persistence']['classes'][$className])) {
				$persistenceSettings = $extbaseSettings['persistence']['classes'][$className];
				if (is_string($persistenceSettings['mapping']['tableName']) && strlen($persistenceSettings['mapping']['tableName']) > 0) {
					$tableName = $persistenceSettings['mapping']['tableName'];
				}
				if (is_array($persistenceSettings['mapping']['columns'])) {
					$columnMapping = $persistenceSettings['mapping']['columns'];
				}
			} elseif (class_exists($className)) {
				foreach (class_parents($className) as $parentClassName) {
					$persistenceSettings = $extbaseSettings['persistence']['classes'][$parentClassName];
					if (is_array($persistenceSettings)) {
						if (is_string($persistenceSettings['mapping']['tableName']) && strlen($persistenceSettings['mapping']['tableName']) > 0) {
							$tableName = $persistenceSettings['mapping']['tableName'];
						}
						if (is_array($persistenceSettings['mapping']['columns'])) {
							$columnMapping = $persistenceSettings['mapping']['columns'];
						}
					}
					break;
				}
			} else {
				throw new Tx_Extbase_Persistence_Exception('Could not determine a Data Map for given class name.', 1256067130);
			}

			$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap($className, $tableName, $columnMapping);
			$this->dataMaps[$className] = $dataMap;
		}
 		return $this->dataMaps[$className];
	}

	/**
	 * Returns the selector (table) name for a given class name.
	 *
	 * @param string $className
	 * @return string The selector name
	 */
	public function convertClassNameToTableName($className) {
		return $this->getDataMap($className)->getTableName();
	}

	/**
	 * Returns the column name for a given property name of the specified class.
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return string The column name
	 */
	public function convertPropertyNameToColumnName($propertyName, $className = '') {
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
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @return string The class name of the child object
	 */
	protected function getType(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName) {
		$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$childClassName = $columnMap->getChildClassName();
		if (!empty($childClassName)) {
			$elementType = $childClassName;
		} elseif (!empty($propertyMetaData['type'])) {
			$elementType = $propertyMetaData['type'];
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('Could not determine the child object object type.', 1251315967);
		}
		return $elementType;		
	}

	/**
	 * Returns the type of the elements inside an ObjectStorage or array.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @return string The class name of the elements inside an ObjectStorage
	 */
	protected function getElementType(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName) {
		$propertyMetaData = $this->reflectionService->getClassSchema(get_class($parentObject))->getProperty($propertyName);
		$columnMap = $this->getDataMap(get_class($parentObject))->getColumnMap($propertyName);
		$childClassName = $columnMap->getChildClassName();
		if (!empty($childClassName)) {
			$elementType = $childClassName;
		} elseif (!empty($propertyMetaData['elementType'])) {
			$elementType = $propertyMetaData['elementType'];
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('Could not determine the type of the contained objects.', 1251315966);
		}
		return $elementType;		
	}

}
?>