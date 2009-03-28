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
	protected $database;
	
	/**
	 * The TYPO3 reference index object
	 *
	 * @var t3lib_refindex
	 **/
	protected $refIndex;
	
	/**
	 * Statistics with counts of database operations
	 *
	 * @var array
	 **/
	protected $statistics = array();
	
	/**
	 * A first level cache for domain objects by class and uid
	 *
	 * @var array
	 **/
	protected $identityMap = array();

	/**
	 * Constructs a new mapper
	 *
	 */
	public function __construct() {
		$this->persistenceSession = t3lib_div::makeInstance('Tx_ExtBase_Persistence_Session');
		$GLOBALS['TSFE']->includeTCA();
		$this->database = $GLOBALS['TYPO3_DB'];
		$this->refIndex = t3lib_div::makeInstance('t3lib_refindex');
	}

	/**
	 * The build query method is invoked by the Persistence Repository.
	 * Build a query for objects by multiple conditions. Either as SQL parts or query by example.
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
	 * @return string The query where part for the class and given conditions
	 */
	public function buildQuery($className, $conditions) {
		$dataMap = $this->getDataMap($className);
		if (is_array($conditions)) {
			$where = $this->buildQueryByConditions($dataMap, $conditions);
		} if (is_string($conditions)) {
			$where = $conditions;
		}
		return $where;
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
	protected function buildQueryByConditions(&$dataMap, $conditions) {
		$whereParts = array();
		foreach ($conditions as $key => $condition) {
			if (is_array($condition) && isset($condition[0])) {
				$sql = $this->replacePlaceholders($dataMap, $condition[0], array_slice($condition, 1));
				$whereParts[] = '(' . $sql . ')';
			} elseif (is_string($key)) {
				$sql = $this->buildQueryByExample($dataMap, $key, $condition);
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
	protected function buildQueryByExample(&$dataMap, $propertyName, $example) {
		$sql = '';
		$columnMap = $dataMap->getColumnMap($propertyName);
		if (!$columnMap) {
			echo "No columnMap for $propertyName";
		}
		if (!is_array($example)) {
			$column = $dataMap->getTableName() . '.' . $columnMap->getColumnName();
			$sql = $column . ' = ' . $dataMap->convertPropertyValueToFieldValue($example);
		} else {
			$childDataMap = $this->getDataMap($columnMap->getChildClassName());
			$sql = $this->buildQueryByConditions($childDataMap, $example);
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
			$markPosition = strpos($sql, '?');
			if ($markPosition !== FALSE) {
				$sql = substr($sql, 0, $markPosition) . $dataMap->convertPropertyValueToFieldValue($parameter) . substr($sql, $markPosition + 1);
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
	 * @param string $from FROM statement will default to the tablename of the given class
	 * @param string $groupBy GROUP BY statement
	 * @param string $orderBy ORDER BY statement
	 * @param string $limit LIMIT statement
	 * @return array The matched rows
	 */
	public function fetch($className, $where = '', $from = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		if (!strlen($where)) {
			$where = '1=1';
		}
		$dataMap = $this->getDataMap($className);
		$joinClause = $this->getJoinClause($className);
		if (!strlen($from)) {
			$from = $dataMap->getTableName() . ' ' . $joinClause;
		}
		if ($useEnableFields === TRUE) {
			$enableFields = $GLOBALS['TSFE']->sys_page->enableFields($dataMap->getTableName());
			// TODO CH: add enable fields for joined tables
		} else {
			$enableFields = '';
		}

		$res = $this->database->exec_SELECTquery(
			'*',
			$from,
			$where . $enableFields,
			$groupBy,
			$orderBy,
			$limit
			);
		$this->statistics['select']++;

		$fieldMap = $this->getFieldMapFromResult($res);
		$rows = $this->getRowsFromResult($res);
		$this->database->sql_free_result($res);

		// SK: Do we want to make it possible to ignore "enableFields"?
		// TODO language overlay; workspace overlay
		$objects = array();
		if (is_array($rows)) {
			if (count($rows) > 0) {
				$objects = $this->reconstituteObjects($dataMap, $fieldMap, $rows);
			}
		}
		$this->statistics['fetch']++;
		return $objects;
	}

	protected function getFieldMapFromResult($res) {
		$fieldMap = array();
		if ($res !== FALSE) {
			$fieldPosition = 0;
			// FIXME mysql_fetch_field should be available in t3lib_db (patch core)
			while ($field = mysql_fetch_field($res)) {
				$fieldMap[$field->table][$field->name] = $fieldPosition;
				$fieldPosition++;
			}
		}
		return $fieldMap;
	}

	protected function getRowsFromResult($res) {
		$rows = array();
		if ($res !== FALSE) {
			while($rows[] = $this->database->sql_fetch_row($res));
			array_pop($rows);
		}
		return $rows;
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
	public function fetchWithRelationTable($parentObject, $columnMap, $where = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		if (!strlen($where)) {
			$where = '1=1';
		}
		$from = $columnMap->getChildTableName() . ' LEFT JOIN ' . $columnMap->getRelationTableName() . ' ON (' . $columnMap->getChildTableName() . '.uid=' . $columnMap->getRelationTableName() . '.uid_foreign)';
		$where .= ' AND ' . $columnMap->getRelationTableName() . '.uid_local=' . t3lib_div::intval_positive($parentObject->getUid());

		return $this->fetch($columnMap->getChildClassName(), $where, $from, $groupBy, $orderBy, $limit, $useEnableFields);
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
	protected function reconstituteObjects(Tx_ExtBase_Persistence_Mapper_DataMap $dataMap, array &$fieldMap, array &$rows) {
		$objects = array();
		foreach ($rows as $row) {
			$properties = $this->getProperties($dataMap, $fieldMap, $row);
			$identity = $properties['uid'];
			$className = $dataMap->getClassName();
			if (isset($this->identityMap[$className][$identity])) {
				$object = $this->identityMap[$className][$identity];
			} else {
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
				$this->identityMap[$className][$identity] = $object;
			}
			$objects[] = $object;
		}
		return $objects;
	}

	protected function getProperties(Tx_ExtBase_Persistence_Mapper_DataMap $dataMap, array &$fieldMap, array &$row) {
		$properties = array();
		foreach ($dataMap->getColumnMaps() as $columnMap) {
			$fieldValue = $row[$fieldMap[$dataMap->getTableName()][$columnMap->getColumnName()]];
			$properties[$columnMap->getPropertyName()] = $dataMap->convertFieldValueToPropertyValue($columnMap->getPropertyName(), $fieldValue);
		}
		return $properties;
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
		$this->statistics['reconstitute']++;
		return $object;
	}

	/**
	 * Persists all objects of a persistence session
	 *
	 * @return void
	 */
	public function persistAll() {
		// first, persist all aggregate root objects
		$aggregateRootClassNames = $this->persistenceSession->getAggregateRootClassNames();
		foreach ($aggregateRootClassNames as $className) {
			$this->persistObjects($className);
		}
		// persist all remaining objects registered manually
		$this->persistObjects();
		// TODO delete objects that are not an aggregate root and lost connection to the parent
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
		foreach ($this->persistenceSession->getRemovedObjects($className) as $object) {
			$this->deleteObject($object);
			$this->persistenceSession->unregisterObject($object);
		}
		foreach ($this->persistenceSession->getDirtyObjects($className) as $object) {
			$this->updateObject($object);
			$this->persistenceSession->unregisterObject($object);
			$this->persistenceSession->registerReconstitutedObject($object);
		}
	}

	/**
	 * Inserts an object to the database. If the object is a value object an
	 * existing instance will be looked up.
	 *
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $object
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $parentObject
	 * @param string $parentPropertyName
	 * @param string $recurseIntoRelations
	 * @return void
	 */
	// SK: I need to check this more thorougly
	protected function insertObject(Tx_ExtBase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = TRUE) {
		$properties = $object->_getProperties();
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$row = $this->getRow($dataMap, $properties);
		
		if ($object instanceof Tx_ExtBase_DomainObject_AbstractValueObject) {
			$conditions = $properties;
			unset($conditions['uid']);
			$where = $this->buildQuery($className, $conditions);
			$existingValueObjects = $this->fetch($className, $where);
			if (count($existingValueObjects)) {
				$existingObject = $existingValueObjects[0];
				$object->_reconstituteProperty('uid', $existingObject->getUid());
				return;
			}
		}

		if ($parentObject instanceof Tx_ExtBase_DomainObject_DomainObjectInterface && $parentPropertyName !== NULL) {
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

		if ($dataMap->hasPidColumn()) {
			$row['pid'] = !empty($this->cObj->data['pages']) ? $this->cObj->data['pages'] : $GLOBALS['TSFE']->id;
		}
		if ($dataMap->hasCreationDateColumn()) {
			$row[$dataMap->getCreationDateColumnName()] = time();
		}
		if ($dataMap->hasTimestampColumn()) {
			$row[$dataMap->getTimestampColumnName()] = time();
		}
		unset($row['uid']);
		$tableName = $dataMap->getTableName();
		$res = $this->database->exec_INSERTquery(
			$tableName,
			$row
			);
		$this->statistics['insert']++;
		$uid = $this->database->sql_insert_id();
		$object->_reconstituteProperty('uid', $uid);
		
		$this->refIndex->updateRefIndexTable($tableName, $uid);

		$this->persistRelations($object, $propertyName, $this->getRelations($dataMap, $properties));
	}

	/**
	 * Updates a modified object in the database
	 *
	 * @return void
	 */
	// SK: I need to check this more thorougly
	protected function updateObject(Tx_ExtBase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = TRUE) {
		$properties = $object->_getDirtyProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$row = $this->getRow($dataMap, $properties);

		if ($parentObject instanceof Tx_ExtBase_DomainObject_DomainObjectInterface && $parentPropertyName !== NULL) {
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
		if ($dataMap->hasTimestampColumn()) {
			$row[$dataMap->getTimestampColumnName()] = time();
		}
		if ($dataMap->hasCreatorUidColumn() && !empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
			$row[$dataMap->getCreatorUidColumnName()] = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
		$tableName = $dataMap->getTableName();
		$res = $this->database->exec_UPDATEquery(
			$tableName,
			'uid=' . $object->getUid(),
			$row
			);
		$this->statistics['update']++;

		$this->persistRelations($object, $propertyName, $this->getRelations($dataMap, $properties));
	}

	/**
	 * Deletes an object, it's 1:n related objects, and the m:n relations in relation tables (but not the m:n related objects!)
	 *
	 * @return void
	 */
	// SK: I need to check this more thorougly
	protected function deleteObject(Tx_ExtBase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = FALSE, $onlySetDeleted = TRUE) {
		$relations = array();
		$properties = $object->_getDirtyProperties();
		$dataMap = $this->getDataMap(get_class($object));
		$relations = $this->getRelations($dataMap, $properties);

		$tableName = $dataMap->getTableName();
		if ($onlySetDeleted === TRUE && $dataMap->hasDeletedColumn()) {
			$deletedColumnName = $dataMap->getDeletedColumnName();
			$res = $this->database->exec_UPDATEquery(
				$tableName,
				'uid=' . $object->getUid(),
				array($deletedColumnName => 1)
				);
			$this->statistics['update']++;
		} else {
			$res = $this->database->exec_DELETEquery(
				$tableName,
				'uid=' . $object->getUid()
				);
			$this->statistics['delete']++;
		}
		$this->refIndex->updateRefIndexTable($tableName, $uid);

		if ($recurseIntoRelations === TRUE) {
			// FIXME disabled, recursive delete has to be implemented
			// $this->processRelations($object, $propertyName, $relations);
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
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function persistRelations(Tx_ExtBase_DomainObject_DomainObjectInterface $object, $propertyName, array $relations) {
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
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function deleteRelations(Tx_ExtBase_DomainObject_DomainObjectInterface $object, $propertyName, array $relations) {
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
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $relatedObject The related object
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function insertRelationInRelationTable(Tx_ExtBase_DomainObject_DomainObjectInterface $relatedObject, Tx_ExtBase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$rowToInsert = array(
			'uid_local' => $parentObject->getUid(),
			'uid_foreign' => $relatedObject->getUid(),
			'tablenames' => $dataMap->getTableName(),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->database->exec_INSERTquery(
			$tableName,
			$rowToInsert
			);
		$this->statistics['insert']++;
	}

	/**
	 * Update relations in a relation table
	 *
	 * @param array $relatedObjects An array of related objects
	 * @param Tx_ExtBase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function deleteRelationInRelationTable($relatedObject, Tx_ExtBase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->database->exec_SELECTquery(
			'uid_foreign',
			$tableName,
			'uid_local=' . $parentObject->getUid()
			);
		$this->statistics['select']++;
		$existingRelations = array();
		while($row = $this->database->sql_fetch_assoc($res)) {
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
			$res = $this->database->exec_DELETEquery(
				$tableName,
				'uid_local=' . $parentObject->getUid() . ' AND uid_foreign IN (' . $relationsToDeleteList . ')'
				);
			$this->statistics['delete']++;
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
		$dataMap = $this->getDataMap($className);
		return $dataMap->isPersistableProperty($propertyName);
	}

	/**
	 * Returns a data map for a given class name
	 *
	 * @return Tx_ExtBase_Persistence_Mapper_DataMap The data map
	 */
	protected function getDataMap($className) {
		if (empty($this->dataMaps[$className])) {
			$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap($className);
			$this->dataMaps[$className] = $dataMap;
		}
		return $this->dataMaps[$className];
	}
	
	public function getStatistics() {
		return $this->statistics;
	}
}
?>