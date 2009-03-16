<?php

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

require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');
require_once(PATH_tslib . 'class.tslib_content.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Utility/TX_EXTMVC_Utility_Strings.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_ObjectStorage.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/Mapper/TX_EXTMVC_Persistence_Mapper_DataMap.php');

/**
 * A mapper to map database tables configured in $TCA on domain objects.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper implements t3lib_Singleton {

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 **/
	protected $cObj;
		
	/**
	 * The persistence session
	 *
	 * @var TX_EXTMVC_Persistence_Session
	 **/
	protected $session;
		
	/**
	 * Cached data maps
	 *
	 * @var array
	 **/
	protected $dataMaps = array();
		
	/**
	 * Constructs a new mapper
	 *
	 */
	public function __construct() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		$GLOBALS['TSFE']->includeTCA();
	}
	
	/**
	 * Finds objects matching a given WHERE Clause
	 *
	 * @param string $className The class name
	 * @param string $arguments The WHERE statement
	 * @return void
	 */
	public function findWhere($className, $where = '1=1') {
		$dataMap = $this->getDataMap($className);
		$rows = $this->fetch($dataMap, $where);
		$objects = $this->reconstituteObjects($dataMap, $rows);
		return $objects;
	}
	
	/**
	 * Fetches rows from the database by given SQL statement snippets
	 *
	 * @param string $from FROM statement
	 * @param string $where WHERE statement
	 * @param string $groupBy GROUP BY statement
	 * @param string $orderBy ORDER BY statement
	 * @param string $limit LIMIT statement
	 * @return void
	 */
	public function fetch($dataMap, $where = '1=1', $groupBy = '', $orderBy = '', $limit = '') {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*', // TODO limit fetched fields
			$dataMap->getTableName(),
			$where . $this->cObj->enableFields($dataMap->getTableName()) . $this->cObj->enableFields($dataMap->getTableName()),
			$groupBy,
			$orderBy,
			$limit
			);
		// TODO language overlay; workspace overlay
		return $rows ? $rows : array();
	}
		
	/**
	 * Fetches a rows from the database by given SQL statement snippets taking a relation table into account
	 *
	 * @param string Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT! You have to prepend 'AND ' to this parameter yourself!
	 * @param string Optional GROUP BY field(s), if none, supply blank string.
	 * @param string Optional ORDER BY field(s), if none, supply blank string.
	 * @param string Optional LIMIT value ([begin,]max), if none, supply blank string.
	 */
	public function fetchWithRelationTable($parentObject, $columnMap, $where = '1=1', $groupBy = '', $orderBy = '', $limit = '') {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			$columnMap->getChildTableName() . '.*, ' . $columnMap->getRelationTableName() . '.*',
			$columnMap->getChildTableName() . ' LEFT JOIN ' . $columnMap->getRelationTableName() . ' ON (' . $columnMap->getChildTableName() . '.uid=' . $columnMap->getRelationTableName() . '.uid_foreign)',
			$where . ' AND ' . $columnMap->getRelationTableName() . '.uid_local=' . intval($parentObject->getUid()) . $this->cObj->enableFields($columnMap->getChildTableName()) . $this->cObj->enableFields($columnMap->getChildTableName()),
			$groupBy,
			$orderBy,
			$limit
			);
		// TODO language overlay; workspace overlay; sorting
		return $rows ? $rows : array();		
	}
	
	/**
	 * reconstitutes domain objects from $rows (array)
	 *
	 * @param TX_EXTMVC_Persistence_Mapper_DataMap $dataMap The data map corresponding to the domain object
	 * @param array $rows The rows array fetched from the database
	 * @return array An array of reconstituted domain objects
	 */
	protected function reconstituteObjects($dataMap, array $rows) {
		$objects = array();
		foreach ($rows as $row) {
			$properties = array();
			foreach ($dataMap->getColumnMaps() as $columnMap) {
				$properties[$columnMap->getPropertyName()] = $dataMap->convertFieldValueToPropertyValue($columnMap->getPropertyName(), $row[$columnMap->getColumnName()]);
			}
			$object = $this->reconstituteObject($dataMap->getClassName(), $properties);
			foreach ($dataMap->getColumnMaps() as $columnMap) {
				if ($columnMap->getTypeOfRelation() === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
					$where = $columnMap->getParentKeyFieldName() . '=' . intval($object->getUid());
					$relatedDataMap = $this->getDataMap($columnMap->getChildClassName());
					$relatedRows = $this->fetch($relatedDataMap, $where);
					$relatedObjects = $this->reconstituteObjects($relatedDataMap, $relatedRows, $depth);
					$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObjects);
				} elseif ($columnMap->getTypeOfRelation() === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
					$relatedDataMap = $this->getDataMap($columnMap->getChildClassName());
					$relatedRows = $this->fetchWithRelationTable($object, $columnMap);
					$relatedObjects = $this->reconstituteObjects($relatedDataMap, $relatedRows, $depth);
					$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObjects);
				}
			}
			$this->session->registerReconstitutedObject($object);			
			$objects[] = $object;
		}
		return $objects;
	}
	
	/**
	 * Reconstitutes the specified object and fills it with the given properties.
	 *
	 * @param string $objectName Name of the object to reconstitute
	 * @param array $properties The names of properties and their values which should be set during the reconstitution
	 * @return object The reconstituted object
	 */
	protected function reconstituteObject($className, array $properties = array()) {
		// those objects will be fetched from within the __wakeup() method of the object...
		$GLOBALS['EXTMVC']['reconstituteObject']['properties'] = $properties;
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		unset($GLOBALS['EXTMVC']['reconstituteObject']);
		return $object;
	}
	
	/**
	 * Persists all objects of a persistence session
	 *
	 * @return void
	 */
	public function persistAll() {
		// first, persit all aggregate root objects
		$aggregateRootClassNames = $this->session->getAggregateRootClassNames();
		foreach ($aggregateRootClassNames as $className) {
			$this->persistObjects($className);
		}
		// persist all remaining objects registered manually
		// $this->persistObjects();
	}
	
	/**
	 * Persists all objects of a persitance session that are of a given class. If there
	 * is no class specified, it persits all objects of a session.
	 *
	 * @param string $className Name of the class of the objects to be persisted
	 */
	protected function persistObjects($className = NULL) {
		foreach ($this->session->getAddedObjects($className) as $object) {
			$this->insertObject($object);
			$this->session->unregisterObject($object);
			$this->session->registerReconstitutedObject($object);
		}
		foreach ($this->session->getDirtyObjects($className) as $object) {
			$this->updateObject($object);
			$this->session->unregisterObject($object);
			$this->session->registerReconstitutedObject($object);
		}
		foreach ($this->session->getRemovedObjects($className) as $object) {
			$this->deleteObject($object);
			$this->session->unregisterObject($object);
		}
	}
	
	/**
	 * Inserts an object to the database.
	 *
	 * @return void
	 */
	protected function insertObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL) {
		$properties = $object->_getProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$relations = $this->getRelations($dataMap, $properties);
		$row = $this->getRow($dataMap, $properties);
		
		if ($parentObject instanceof TX_EXTMVC_DomainObject_AbstractDomainObject && $parentPropertyName !== NULL) {
			$parentDataMap = $this->getDataMap(get_class($parentObject));
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
			$parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
			if ($parentKeyFieldName !== NULL) {
				$row[$parentKeyFieldName] = $parentObject->getUid();
			}
			$parentTableFieldName = $parentColumnMap->getParentTableFieldName();
			if ($parentTableFieldName !== NULL) {
				$row[$parentTableFieldName] = $parentDataMap->getTableName();
			}
		}

		unset($row['uid']);
		$row['pid'] = !empty($this->cObj->data['pages']) ? $this->cObj->data['pages'] : $GLOBALS['TSFE']->id;
		$row['tstamp'] = time();
		
		$tableName = $dataMap->getTableName();
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$tableName,
			$row
			);
		$object->_reconstituteProperty('uid', $GLOBALS['TYPO3_DB']->sql_insert_id());
		
		$recursionMode = TRUE; // TODO make parametric
		if ($recursionMode === TRUE) {
			$this->processRelations($object, $propertyName, 'persist', $relations);
		}
	}
		
	/**
	 * Updates a modified object in the database
	 *
	 * @return void
	 */
	protected function updateObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL) {
		$properties = $object->_getDirtyProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$relations = $this->getRelations($dataMap, $properties);
		$row = $this->getRow($dataMap, $properties);

		unset($row['uid']);
		$row['crdate'] = time();
		if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
			$row['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
		if ($parentObject instanceof TX_EXTMVC_DomainObject_AbstractDomainObject && $parentPropertyName !== NULL) {
			$parentDataMap = $this->getDataMap(get_class($parentObject));
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
			$parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
			if ($parentKeyFieldName !== NULL) {
				$row[$parentKeyFieldName] = $parentObject->getUid();
			}
			$parentTableFieldName = $parentColumnMap->getParentTableFieldName();
			if ($parentTableFieldName !== NULL) {
				$row[$parentTableFieldName] = $parentDataMap->getTableName();
			}
		}

		$tableName = $dataMap->getTableName();
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$tableName,
			'uid=' . $object->getUid(),
			$row
			);

		$recursionMode = TRUE; // TODO make parametric
		if ($recursionMode === TRUE) {
			$this->processRelations($object, $propertyName, 'persist', $relations);
		}
	}

	/**
	 * Deletes an object, it's 1:n related objects, and the m:n relations in relation tables (but not the m:n related objects!)
	 *
	 * @return void
	 */
	protected function deleteObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL, $recursionMode = FALSE, $onlySetDeleted = TRUE) {
		$relations = array();
		$properties = $object->_getDirtyProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$relations = $this->getRelations($dataMap, $properties);

		$tableName = $dataMap->getTableName();
		if ($onlySetDeleted === TRUE && !empty($deletedColumnName)) {
			$deletedColumnName = $dataMap->getDeletedColumnName();
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				$tableName,
				'uid=' . $object->getUid(),
				array($deletedColumnName => 1)
				);
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$tableName,
				'uid=' . $object->getUid()
				);
		}
		
		if ($recursionMode === TRUE) {
			$this->processRelations($object, $propertyName, 'delete', $relations);
		}		
	}
	
	/**
	 * Returns a table row to be inserted or updated in the database
	 *
	 * @param TX_EXTMVC_Persistence_Mapper_DataMap $dataMap The appropriate data map representing a database table
	 * @param string $properties The properties of the object
	 * @return array A single row to be inserted in the database
	 */
	protected function getRow(TX_EXTMVC_Persistence_Mapper_DataMap $dataMap, $properties) {
		$relations = array();
		foreach ($dataMap->getColumnMaps() as $columnMap) {
			$propertyName = $columnMap->getPropertyName();
			$columnName = $columnMap->getColumnName();
			if ($columnMap->getTypeOfRelation() === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
				$row[$columnName] = count($properties[$propertyName]);
			} elseif ($columnMap->getTypeOfRelation() === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
				$row[$columnName] = count($properties[$propertyName]);
			} else {
				if ($properties[$propertyName] !== NULL) {
					$row[$columnName] = $dataMap->convertPropertyValueToFieldValue($properties[$propertyName]);
				}
			}
		}
		return $row;
	}
	
	/**
	 * Returns all property values holding child objects
	 *
	 * @param TX_EXTMVC_Persistence_Mapper_DataMap $dataMap The data map
	 * @param string $properties The object properties
	 * @return array An array of properties with related child objects
	 */
	protected function getRelations(TX_EXTMVC_Persistence_Mapper_DataMap $dataMap, $properties) {
		$relations = array();
		foreach ($dataMap->getColumnMaps() as $columnMap) {
			$propertyName = $columnMap->getPropertyName();
			$columnName = $columnMap->getColumnName();
			if ($columnMap->isRelation()) {
				$relations[$propertyName] = $properties[$propertyName];
			}
		}
		return $relations;
	}
	
	/**
	 * Processes all relations of an object. It also updates relation tables.
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param string $command The command (one of "persist", "delete"). Persist updates and inserts records as needed
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function processRelations(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $propertyName, $command, array $relations) {
		$dataMap = $this->getDataMap(get_class($object));
		if ($command === 'persist') {
			foreach ($relations as $propertyName => $relatedObjects) {
				if (!empty($relatedObjects)) {
					$typeOfRelation = $dataMap->getColumnMap($propertyName)->getTypeOfRelation();
					foreach ($relatedObjects as $relatedObject) {
						if (!$this->session->isReconstitutedObject($relatedObject)) {
							$this->insertObject($relatedObject, $object, $propertyName);
							if ($typeOfRelation === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
								$this->insertRelationInRelationTable($relatedObject, $object, $propertyName);
							}
						} elseif ($this->session->isReconstitutedObject($relatedObject) && $relatedObject->_isDirty()) {
							$this->updateObject($relatedObject, $object, $propertyName);
						}
					}
				}
				if ($typeOfRelation === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
					$this->updateRelationsInRelationTable($relatedObjects, $object, $propertyName);
				}
			}
		} elseif ($command === 'delete') {
			foreach ($relations as $propertyName => $relatedObjects) {
				foreach ($relatedObjects as $relatedObject) {
					$this->deleteObject($relatedObject, $object, $propertyName);
					if ($dataMap->getColumnMap($propertyName)->getTypeOfRelation() === TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
						$this->deleteRelation($relatedObject, $object, $propertyName);
					}
				}
			}		
		}
	}
	
	/**
	 * Inserts relation to a relation table
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $relatedObject The related object
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in 
	 * @return void
	 */
	protected function insertRelationInRelationTable(TX_EXTMVC_DomainObject_AbstractDomainObject $relatedObject, TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$rowToInsert = array(
			'uid_local' => $parentObject->getUid(),
			'uid_foreign' => $relatedObject->getUid(),
			'tablenames' => $dataMap->getTableName(),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$tableName,
			$rowToInsert
			);
	}
	
	/**
	 * Update relations in a relation table
	 *
	 * @param array $relatedObjects An array of related objects
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in 
	 * @return void
	 */
	protected function updateRelationsInRelationTable($relatedObjects, TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_foreign',
			$tableName,
			'uid_local=' . $parentObject->getUid()
			);
		$existingRelations = array();
		while($row = mysql_fetch_assoc($res)) {
			$existingRelations[current($row)] = current($row);
		}
		$relationsToDelete = $existingRelations;
		if (is_array($relatedObjects)) {
			foreach ($relatedObjects as $relatedObject) {
				$relatedObjectUid = $relatedObject->getUid();
				if (array_key_exists($relatedObjectUid, $relationsToDelete)) {
					unset($relationsToDelete[$relatedObjectUid]);
				}
			}			
		}
		if (count($relationsToDelete) > 0) {
			$relationsToDeleteList = implode(',', $relationsToDelete);
			$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$tableName,
				'uid_local=' . $parentObject->getUid() . ' AND uid_foreign IN (' . $relationsToDeleteList . ')'
				);
		}
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
		$dataMap = new TX_EXTMVC_Persistence_Mapper_DataMap($className);
		$dataMap->initialize();
		return $dataMap->isPersistableProperty($propertyName);
	}
	
	/**
	 * Returns a data map for a given class name
	 *
	 * @return TX_EXTMVC_Persistence_Mapper_DataMap The data map
	 */
	protected function getDataMap($className) {
		// TODO Cache data maps
		if (empty($this->dataMaps[$className])) {
			$dataMap = new TX_EXTMVC_Persistence_Mapper_DataMap($className);
			$dataMap->initialize();
			$this->dataMaps[$className] = $dataMap;
		}
		return $this->dataMaps[$className];
	}
	
}
?>