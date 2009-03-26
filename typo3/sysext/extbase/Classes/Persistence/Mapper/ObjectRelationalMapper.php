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

require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');
require_once(PATH_tslib . 'class.tslib_content.php');

/**
 * A mapper to map database tables configured in $TCA on domain objects.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_ExtBase_Persistence_Mapper_ObjectRelationalMapper implements t3lib_Singleton {

	/**
	 * The persistence session
	 *
	 * @var Tx_ExtBase_Persistence_Session
	 **/
	protected $persistenceSession;

	/**
	 * Cached data maps
	 *
	 * @var array
	 **/
	protected $dataMaps = array();
	
	/**
	 * The TYPO3 DB object
	 *
	 * @var t3lib_db
	 **/
	protected $db;

	/**
	 * Constructs a new mapper
	 *
	 */
	public function __construct() {
		$this->persistenceSession = t3lib_div::makeInstance('Tx_ExtBase_Persistence_Session');
		$GLOBALS['TSFE']->includeTCA();
		$this->db = $GLOBALS['TYPO3_DB'];
	}
	
	/**
	 * This find method is invoked by the Persistence Repository.
	 * Find objects by multiple conditions. Either as SQL parts or query by example. The fin process is delegated
	 * to the data mapper.
	 * 
	 * The following condition array would find entities with description like the given keyword and
	 * name equal to "foo".
	 *
	 * <pre>
	 * array(
	 *   array('blog_description LIKE ?', $keyword),
	 *   	'blogName' => 'Foo'
	 * 	)
	 * </pre>
	 * 
	 * Note: The SQL part uses the database columns names, the query by example syntax uses
	 * the object property name (camel-cased, without underscore).
	 *
	 * @param array|string $conditions The conditions as an array or SQL string
	 * @param string $groupBy Group by SQL part
	 * @param string $orderBy Order by SQL part
	 * @param string $limit Limit SQL part
	 * @param bool $useEnableFields Wether to automatically restrict the query by enable fields
	 * @return array An array of objects, empty if no objects found
	 * @see Repository#find(...)
	 */
	public function find($className, $conditions, $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		$dataMap = $this->getDataMap($className);
		if (is_array($conditions)) {
			$where = $this->queryByConditions($dataMap, $conditions);
		} elseif (is_string($conditions)) {
			if (strlen($conditions) === 0) {
				$where = '1=1';
			} else {
				$where = $conditions;
			}
		}
		return $this->fetch($className, $where, $groupBy, $orderBy, $limit, $useEnableFields);
	}
	
	/**
	 * Get a where part for conditions by a specific data map. This will
	 * either replace placeholders (index based array) or use the condition
	 * as an example relative to the data map.
	 *
	 * @param Tx_ExtBase_Persistence_Mapper_DataMap $dataMap The data map
	 * @param array $conditions The conditions
	 * 
	 * @return string The where part
	 */
	protected function queryByConditions(&$dataMap, $conditions) {
		$whereParts = array();
		foreach ($conditions as $key => $condition) {
			if (is_array($condition) && isset($condition[0])) {
				$sql = $this->replacePlaceholders($dataMap, $condition[0], array_slice($condition, 1));
				$whereParts[] = '(' . $sql . ')';
			} elseif (is_string($key)) {
				$sql = $this->queryByExample($dataMap, $key, $condition);
				if (strlen($sql) > 0) {
					$whereParts[] = '(' . $sql . ')';
				}
			}
		}
		return implode(' AND ', $whereParts);		
	}

	/**
	 * Get a where part for an example condition (associative array). This also works
	 * for nested conditions.
	 *
	 * @param Tx_ExtBase_Persistence_Mapper_DataMap $dataMap The data map
	 * @param array $propertyName The property name
	 * @param array $example The example condition
	 * 
	 * @return string The where part
	 */
	protected function queryByExample(&$dataMap, $propertyName, $example) {
		$sql = '';
		if (!is_array($example)) {
			$column = $dataMap->getTableName() . '.' . $dataMap->getColumnMap($propertyName)->getColumnName();
			$sql = $column . ' = ' . $dataMap->convertPropertyValueToFieldValue($example);
		} else {
			$columnMap = $dataMap->getColumnMap($propertyName);
			$childDataMap = $this->getDataMap($columnMap->getChildClassName());
			$sql = $this->queryByConditions($childDataMap, $example);
		}
		return $sql;
	}
	
	/**
	 * Replace query placeholders in a query part by the given
	 * parameters.
	 *
	 * @param Tx_ExtBase_Persistence_Mapper_DataMap $dataMap The data map for conversion
	 * @param string $queryPart The query part with placeholders
	 * @param array $parameters The parameters
	 *
	 * @return string The query part with replaced placeholders
	 */
	protected function replacePlaceholders(&$dataMap, $queryPart, $parameters) {
		$sql = $queryPart;
		foreach ($parameters as $parameter) {
			$markPos = strpos($sql, '?');
			if ($markPos !== FALSE) {
				$sql = substr($sql, 0, $markPos) . $dataMap->convertPropertyValueToFieldValue($parameter) . substr($sql, $markPos + 1);
			}
		}
		return $sql;
	}

	/**
	 * Fetches objects from the database by given SQL statement snippets. The where
	 * statement is raw SQL and will not be escaped. It is much safer to use the
	 * generic find method to supply where conditions.
	 *
	 * @param string $className the className
	 * @param string $where WHERE statement
	 * @param string $groupBy GROUP BY statement
	 * @param string $orderBy ORDER BY statement
	 * @param string $limit LIMIT statement
	 * @return array The matched rows
	 */
	public function fetch($className, $where = '1=1', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		$dataMap = $this->getDataMap($className);
		if ($useEnableFields === TRUE) {
			$enableFields = $GLOBALS['TSFE']->sys_page->enableFields($dataMap->getTableName());
		} else {
			$enableFields = '';
		}
		
		$joinTables = $this->getJoinClause($className);
		$res = $this->db->exec_SELECTquery(
			'*', // TODO limit fetched fields (CH: should we do that? JR: Not needed; only existing properties will be mapped)
			$dataMap->getTableName() . ' ' . $joinTables,
			$where . $enableFields,
			$groupBy,
			$orderBy,
			$limit
			);
		$fieldMap = array();
		$i = 0;
		// FIXME mysql_fetch_field should be available in t3lib_db (patch core)
		$rows = array();
		if ($res !== FALSE) {
			while ($field = mysql_fetch_field($res)) {
				$fieldMap[$field->table][$field->name] = $i;
				$i++;
			}
			while($rows[] = $this->db->sql_fetch_row($res));
			array_pop($rows);
		}

		
		// SK: Do we want to make it possible to ignore "enableFields"?
		// TODO language overlay; workspace overlay
		$objects = array();
		if (is_array($rows)) {
			if (count($rows) > 0) {
				$objects = $this->reconstituteObjects($dataMap, $fieldMap, $rows);
			}
		}
		return $objects;
	}
	
	/**
	 * Get the join clause for the fetch method for a specific class. This will
	 * eagerly load all has-one relations.
	 *
	 * @param string $className The class name
	 * @return string The join clause
	 */
	protected function getJoinClause($className) {
		$dataMap = $this->getDataMap($className);
		$join = '';
		foreach ($dataMap->getColumnMaps() as $propertyName => $columnMap) {
			if ($columnMap->getTypeOfRelation() == Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
				$join .= ' LEFT JOIN ' . $columnMap->getChildTableName() . ' ON ' . $dataMap->getTableName() . '.' . $columnMap->getColumnName() . ' = ' . $columnMap->getChildTableName() . '.uid';
				$join .= $this->getJoinClause($columnMap->getChildClassName());
			}
		}
		return $join;
	}

	/**
	 * Fetches a rows from the database by given SQL statement snippets taking a relation table into account
	 *
	 * @param string Optional WHERE clauses put in the end of the query, defaults to '1=1. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param string Optional GROUP BY field(s), defaults to blank string.
	 * @param string Optional ORDER BY field(s), defaults to blank string.
	 * @param string Optional LIMIT value ([begin,]max), defaults to blank string.
	 */
	public function fetchWithRelationTable($parentObject, $columnMap, $where = '1=1', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		if ($useEnableFields === TRUE) {
			$enableFields = $GLOBALS['TSFE']->sys_page->enableFields($columnMap->getChildTableName());
		} else {
			$enableFields = '';
		}
		$rows = $this->db->exec_SELECTgetRows(
			$columnMap->getChildTableName() . '.*, ' . $columnMap->getRelationTableName() . '.*',
			$columnMap->getChildTableName() . ' LEFT JOIN ' . $columnMap->getRelationTableName() . ' ON (' . $columnMap->getChildTableName() . '.uid=' . $columnMap->getRelationTableName() . '.uid_foreign)',
			$where . ' AND ' . $columnMap->getRelationTableName() . '.uid_local=' . t3lib_div::intval_positive($parentObject->getUid()) . $enableFields,
			$groupBy,
			$orderBy,
			$limit
			);
		// TODO language overlay; workspace overlay; sorting
		$objects = array();
		if (is_array($rows)) {
			if (count($rows) > 0) {
				$objects = $this->reconstituteObjects($dataMap, $fieldMap, $rows);
			}
		}
		return $objects;
	}

	/**
	 * reconstitutes domain objects from $rows (array)
	 *
	 * @param Tx_ExtBase_Persistence_Mapper_DataMap $dataMap The data map corresponding to the domain object
	 * @param array $fieldMap An array indexed by the table name and field name to the row index
	 * @param array $rows The rows array fetched from the database (not associative)
	 * @return array An array of reconstituted domain objects
	 */
	// SK: I Need to check this method more thoroughly.
	// SK: Are loops detected during reconstitution?
	protected function reconstituteObjects($dataMap, &$fieldMap, array $rows) {
		$objects = array();
		foreach ($rows as $row) {
			$properties = array();
			foreach ($dataMap->getColumnMaps() as $columnMap) {
				$fieldValue = $row[$fieldMap[$dataMap->getTableName()][$columnMap->getColumnName()]];
				$properties[$columnMap->getPropertyName()] = $dataMap->convertFieldValueToPropertyValue($columnMap->getPropertyName(), $fieldValue);
			}
			$object = $this->reconstituteObject($dataMap->getClassName(), $properties);
			foreach ($dataMap->getColumnMaps() as $columnMap) {
				if ($columnMap->getTypeOfRelation() === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
					list($relatedObject) = $this->reconstituteObjects($this->getDataMap($columnMap->getChildClassName()), $fieldMap, array($row));
					$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObject);
				} elseif ($columnMap->getTypeOfRelation() === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
					$where = $columnMap->getParentKeyFieldName() . '=' . intval($object->getUid());
					$relatedDataMap = $this->getDataMap($columnMap->getChildClassName());
					$relatedObjects = $this->fetch($columnMap->getChildClassName(), $where);
					$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObjects);
				} elseif ($columnMap->getTypeOfRelation() === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
					$relatedDataMap = $this->getDataMap($columnMap->getChildClassName());
					$relatedObjects = $this->fetchWithRelationTable($object, $columnMap);
					$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObjects);
				}
			}
			$this->persistenceSession->registerReconstitutedObject($object);
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
		$GLOBALS['ExtBase']['reconstituteObject']['properties'] = $properties;
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		unset($GLOBALS['ExtBase']['reconstituteObject']);
		return $object;
	}

	/**
	 * Persists all objects of a persistence session
	 *
	 * @return void
	 */
	public function persistAll() {
		// first, persit all aggregate root objects
		$aggregateRootClassNames = $this->persistenceSession->getAggregateRootClassNames();
		foreach ($aggregateRootClassNames as $className) {
			$this->persistObjects($className);
		}
		// persist all remaining objects registered manually
		// $this->persistObjects();
	}

	/**
	 * Persists all objects of a persitance persistence session that are of a given class. If there
	 * is no class specified, it persits all objects of a persistence session.
	 *
	 * @param string $className Name of the class of the objects to be persisted
	 */
	protected function persistObjects($className = NULL) {
		foreach ($this->persistenceSession->getAddedObjects($className) as $object) {
			$this->insertObject($object);
			$this->persistenceSession->unregisterObject($object);
			$this->persistenceSession->registerReconstitutedObject($object);
		}
		foreach ($this->persistenceSession->getDirtyObjects($className) as $object) {
			$this->updateObject($object);
			$this->persistenceSession->unregisterObject($object);
			$this->persistenceSession->registerReconstitutedObject($object);
		}
		foreach ($this->persistenceSession->getRemovedObjects($className) as $object) {
			$this->deleteObject($object);
			$this->persistenceSession->unregisterObject($object);
		}
	}

	/**
	 * Inserts an object to the database.
	 *
	 * @return void
	 */
	// SK: I need to check this more thorougly
	protected function insertObject(Tx_ExtBase_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = TRUE) {
		$properties = $object->_getProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$row = $this->getRow($dataMap, $properties);

		if ($parentObject instanceof Tx_ExtBase_DomainObject_AbstractDomainObject && $parentPropertyName !== NULL) {
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
		$res = $this->db->exec_INSERTquery(
			$tableName,
			$row
			);
		$object->_reconstituteProperty('uid', $this->db->sql_insert_id());

		$this->persistRelations($object, $propertyName, $this->getRelations($dataMap, $properties));
	}

	/**
	 * Updates a modified object in the database
	 *
	 * @return void
	 */
	// SK: I need to check this more thorougly
	protected function updateObject(Tx_ExtBase_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = TRUE) {
		$properties = $object->_getDirtyProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$row = $this->getRow($dataMap, $properties);
		unset($row['uid']);
		// TODO Check for crdate column
		$row['crdate'] = time();
		if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
			$row['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
		if ($parentObject instanceof Tx_ExtBase_DomainObject_AbstractDomainObject && $parentPropertyName !== NULL) {
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
		$res = $this->db->exec_UPDATEquery(
			$tableName,
			'uid=' . $object->getUid(),
			$row
			);

		$this->persistRelations($object, $propertyName, $this->getRelations($dataMap, $properties));
	}

	/**
	 * Deletes an object, it's 1:n related objects, and the m:n relations in relation tables (but not the m:n related objects!)
	 *
	 * @return void
	 */
	// SK: I need to check this more thorougly
	protected function deleteObject(Tx_ExtBase_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = FALSE, $onlySetDeleted = TRUE) {
		$relations = array();
		$properties = $object->_getDirtyProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$relations = $this->getRelations($dataMap, $properties);

		$tableName = $dataMap->getTableName();
		if ($onlySetDeleted === TRUE && !empty($deletedColumnName)) {
			$deletedColumnName = $dataMap->getDeletedColumnName();
			$res = $this->db->exec_UPDATEquery(
				$tableName,
				'uid=' . $object->getUid(),
				array($deletedColumnName => 1)
				);
		} else {
			$res = $this->db->exec_DELETEquery(
				$tableName,
				'uid=' . $object->getUid()
				);
		}

		if ($recurseIntoRelations === TRUE) {
			$this->processRelations($object, $propertyName, $relations);
		}
	}

	/**
	 * Returns a table row to be inserted or updated in the database
	 *
	 * @param Tx_ExtBase_Persistence_Mapper_DataMap $dataMap The appropriate data map representing a database table
	 * @param array $properties The properties of the object
	 * @return array A single row to be inserted in the database
	 */
	// SK: I need to check this more thorougly
	protected function getRow(Tx_ExtBase_Persistence_Mapper_DataMap $dataMap, $properties) {
		$relations = array();
		foreach ($dataMap->getColumnMaps() as $columnMap) {
			$propertyName = $columnMap->getPropertyName();
			$columnName = $columnMap->getColumnName();
			if ($columnMap->getTypeOfRelation() === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
				$row[$columnName] = count($properties[$propertyName]);
			} elseif ($columnMap->getTypeOfRelation() === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
				// TODO Check if this elseif is needed or could be merged with the lines above
				$row[$columnName] = count($properties[$propertyName]);
			} else {
				if ($properties[$propertyName] !== NULL) {
					$row[$columnName] = $dataMap->convertPropertyValueToFieldValue($properties[$propertyName], FALSE);
				}
			}
		}
		return $row;
	}

	/**
	 * Returns all property values holding child objects
	 *
	 * @param Tx_ExtBase_Persistence_Mapper_DataMap $dataMap The data map
	 * @param string $properties The object properties
	 * @return array An array of properties with related child objects
	 */
	protected function getRelations(Tx_ExtBase_Persistence_Mapper_DataMap $dataMap, $properties) {
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
	 * Inserts and updates all relations of an object. It also inserts and updates data in relation tables.
	 *
	 * @param Tx_ExtBase_DomainObject_AbstractDomainObject $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function persistRelations(Tx_ExtBase_DomainObject_AbstractDomainObject $object, $propertyName, array $relations) {
		$dataMap = $this->getDataMap(get_class($object));
		foreach ($relations as $propertyName => $relatedObjects) {
			if (!empty($relatedObjects)) {
				$typeOfRelation = $dataMap->getColumnMap($propertyName)->getTypeOfRelation();
				foreach ($relatedObjects as $relatedObject) {
					if (!$this->persistenceSession->isReconstitutedObject($relatedObject)) {
						$this->insertObject($relatedObject, $object, $propertyName);
						if ($typeOfRelation === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
							$this->insertRelationInRelationTable($relatedObject, $object, $propertyName);
						}
					} elseif ($this->persistenceSession->isReconstitutedObject($relatedObject) && $relatedObject->_isDirty()) {
						$this->updateObject($relatedObject, $object, $propertyName);
					}
				}
			}
		}
	}

	/**
	 * Deletes all relations of an object.
	 *
	 * @param Tx_ExtBase_DomainObject_AbstractDomainObject $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function deleteRelations(Tx_ExtBase_DomainObject_AbstractDomainObject $object, $propertyName, array $relations) {
		$dataMap = $this->getDataMap(get_class($object));
		foreach ($relations as $propertyName => $relatedObjects) {
			if (is_array($relatedObjects)) {
				foreach ($relatedObjects as $relatedObject) {
					$this->deleteObject($relatedObject, $object, $propertyName);
					if ($dataMap->getColumnMap($propertyName)->getTypeOfRelation() === Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
						$this->deleteRelationInRelationTable($relatedObject, $object, $propertyName);
					}
				}
			}
		}
	}

	/**
	 * Inserts relation to a relation table
	 *
	 * @param Tx_ExtBase_DomainObject_AbstractDomainObject $relatedObject The related object
	 * @param Tx_ExtBase_DomainObject_AbstractDomainObject $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function insertRelationInRelationTable(Tx_ExtBase_DomainObject_AbstractDomainObject $relatedObject, Tx_ExtBase_DomainObject_AbstractDomainObject $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$rowToInsert = array(
			'uid_local' => $parentObject->getUid(),
			'uid_foreign' => $relatedObject->getUid(),
			'tablenames' => $dataMap->getTableName(),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->db->exec_INSERTquery(
			$tableName,
			$rowToInsert
			);
	}

	/**
	 * Update relations in a relation table
	 *
	 * @param array $relatedObjects An array of related objects
	 * @param Tx_ExtBase_DomainObject_AbstractDomainObject $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function deleteRelationInRelationTable($relatedObject, Tx_ExtBase_DomainObject_AbstractDomainObject $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->db->exec_SELECTquery(
			'uid_foreign',
			$tableName,
			'uid_local=' . $parentObject->getUid()
			);
		$existingRelations = array();
		while($row = mysql_fetch_assoc($res)) {
			$existingRelations[current($row)] = current($row);
		}
		$relationsToDelete = $existingRelations;
		if (is_array($relatedObject)) {
			foreach ($relatedObject as $relatedObject) {
				$relatedObjectUid = $relatedObject->getUid();
				if (array_key_exists($relatedObjectUid, $relationsToDelete)) {
					unset($relationsToDelete[$relatedObjectUid]);
				}
			}
		}
		if (count($relationsToDelete) > 0) {
			$relationsToDeleteList = implode(',', $relationsToDelete);
			$res = $this->db->exec_DELETEquery(
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
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap($className);
		$dataMap->initialize();
		return $dataMap->isPersistableProperty($propertyName);
	}

	/**
	 * Returns a data map for a given class name
	 *
	 * @return Tx_ExtBase_Persistence_Mapper_DataMap The data map
	 */
	protected function getDataMap($className) {
		// TODO Cache data maps
		if (empty($this->dataMaps[$className])) {
			$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap($className);
			$dataMap->initialize();
			$this->dataMaps[$className] = $dataMap;
		}
		return $this->dataMaps[$className];
	}

}
?>