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
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Mapper_DataMapper implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

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
		$GLOBALS['TSFE']->includeTCA(); // TODO Move this to an appropriate position
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
		$this->persistenceManager = $persistenceManager;
		$this->QOMFactory = $this->persistenceManager->getBackend()->getQOMFactory();
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
		if ($this->identityMap->hasUid($className, $row['uid'])) {
			$object = $this->identityMap->getObjectByUid($className, $row['uid']);
		} else {
			$object = $this->createEmptyObject($className);
			$this->thawProperties($object, $row);
			$this->identityMap->registerObject($object, $object->getUid());
			$object->_memorizeCleanState();
		}
		return $object;
	}

	/**
	 * Creates a skeleton of the specified object
	 *
	 * @param string $className Name of the class to create a skeleton for
	 * @return object The object skeleton
	 * @internal
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
		$object->_setProperty('uid', $row['uid']);
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
					if (isset($row[$columnName])) {
						$rawPropertyValue = $row[$columnName];
						$propertyValue = $dataMap->convertFieldValueToPropertyValue($propertyType, $rawPropertyValue);
					}
				break;
				case (Tx_Extbase_Persistence_PropertyType::REFERENCE):
					if (!is_null($row[$propertyName])) {
						$propertyValue = $this->mapRelatedObjects($object, $propertyName, $row, $columnMap);
					} else {
						$propertyValue = NULL;
					}
				break;
					// FIXME we have an object to handle... -> exception
				default:
					// SK: We should throw an exception as this point as there was an undefined propertyType we can not handle.
					if (isset($row[$propertyName])) {
						$property = $row[$propertyName];
						if (is_object($property)) {
							$propertyValue = $this->mapObject($property);
							// SK: THIS case can not happen I think. At least $this->mapObject() is not available.
						} else {
							// SK: This case does not make sense either. $this-mapSingleRow has a different signature 
							$propertyValue = $this->mapSingleRow($className, $property);
						}
					}
				break;
			}

			$object->_setProperty($propertyName, $propertyValue);
		}
	}

	/**
	 * Maps related objects to an ObjectStorage
	 *
	 * @param object $parentObject The parent object for the mapping result
	 * @param string $propertyName The target property name for the mapping result
	 * @param Tx_Extbase_Persistence_RowInterface $row The actual database row
	 * @param int $loadingStrategy The loading strategy; one of Tx_Extbase_Persistence_Mapper_ColumnMap::STRATEGY_*
	 * @return array|Tx_Extbase_Persistence_ObjectStorage|Tx_Extbase_Persistence_LazyLoadingProxy|another implementation of a loading strategy
	 */
	protected function mapRelatedObjects(Tx_Extbase_DomainObject_AbstractEntity $parentObject, $propertyName, Tx_Extbase_Persistence_RowInterface $row, Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($propertyName);
		if ($columnMap->getLoadingStrategy() === Tx_Extbase_Persistence_Mapper_ColumnMap::STRATEGY_PROXY) {
			// TODO Remove dependency to the loading strategy implementation
			$result = t3lib_div::makeInstance('Tx_Extbase_Persistence_LazyLoadingProxy', $parentObject, $propertyName, $dataMap);
		} else {
			if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
				$query = $this->queryFactory->create($columnMap->getChildClassName());
				$result = current($query->matching($query->withUid($row[$columnMap->getColumnName()]))->execute());
			} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
				$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
				$query = $this->queryFactory->create($columnMap->getChildClassName());
				$objects = $query->matching($query->equals($columnMap->getParentKeyFieldName(), $parentObject->getUid()))->execute();
				foreach ($objects as $object) {
					$objectStorage->attach($object);
				}
				$result = $objectStorage;
			} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
				$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
				$relationTableName = $columnMap->getRelationTableName();
				$left = $this->QOMFactory->selector($relationTableName);
				$childTableName = $columnMap->getChildTableName();
				$right = $this->QOMFactory->selector($childTableName);
				$joinCondition = $this->QOMFactory->equiJoinCondition($relationTableName, 'uid_foreign', $childTableName, 'uid');
				$source = $this->QOMFactory->join(
					$left,
					$right,
					Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER,
					$joinCondition
					);
				$query = $this->queryFactory->create($columnMap->getChildClassName());
				$query->setSource($source);
				$objects = $query->matching($query->equals('uid_local', $parentObject->getUid()))->execute();
				foreach ($objects as $object) {
					$objectStorage->attach($object);
				}
				$result = $objectStorage;
			}
		}

		return $result;
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
	 * @return Tx_Extbase_Persistence_Mapper_DataMap The data map
	 */
	public function getDataMap($className) {
		global $TCA;
		if (empty($this->dataMaps[$className])) {
//			// TODO This is a little bit costy for table name aliases -> implement a DataMapBuilder (knowing the aliases defined in $TCA)
//			$tableName = '';
//			if (is_array($TCA[strtolower($className)] && empty($TCA[strtolower($className)]['config']['classes']))) {
//				$tableName = strtolower($className);
//			} else {
//						debug($TCA);
//
//				foreach ($TCA as $configuredTableName => $tableConfiguration) {
//					if (in_array($className, t3lib_div::trimExplode(',', $tableConfiguration['config']['classes']))) {
//						$tableName = $configuredTableName;
//					}
//				}
//			}
			$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap($className, $tableName);
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
	public function convertClassNameToSelectorName($className) {
		return $this->getDataMap($className)->getTableName();
	}

}
?>