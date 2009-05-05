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
class Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper implements Tx_Extbase_Persistence_DataMapperInterface, t3lib_Singleton {

	/**
	 * Cached data maps
	 *
	 * @var array
	 **/
	protected $dataMaps = array();

	/**
	 * The persistence backend
	 *
	 * @var t3lib_DB
	 **/
	protected $persistenceBackend;

	/**
	 * The TYPO3 reference index object
	 *
	 * @var t3lib_refindex
	 **/
	protected $referenceIndex;

	/**
	 * The aggregate root objects to be handled by the object relational mapper
	 *
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 **/
	protected $aggregateRootObjects;

	/**
	 * The deleted objects to be handled by the object relational mapper
	 *
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 **/
	protected $deletedObjects;

	/**
	 * A first level cache for domain objects by class and uid
	 *
	 * @var array
	 **/
	protected $identityMap = array();

	/**
	 * A reference to the page select object providing methods to perform language and work space overlays
	 *
	 * @var t3lib_pageSelect
	 **/
	protected $pageSelectObject;

	/**
	 * Constructs a new mapper
	 *
	 */
	public function __construct(t3lib_DB $persistenceBackend) {
		$this->persistenceBackend = $persistenceBackend;
		$this->referenceIndex = t3lib_div::makeInstance('t3lib_refindex');
		$this->aggregateRootObjects = new Tx_Extbase_Persistence_ObjectStorage();
		$this->identityMap = new Tx_Extbase_Persistence_IdentityMap();
		$GLOBALS['TSFE']->includeTCA();
	}

	/**
	 * Sets the aggregate root objects
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $objects The objects to be registered
	 * @return void
	 */
	public function setAggregateRootObjects(Tx_Extbase_Persistence_ObjectStorage $objects) {
		$this->aggregateRootObjects = $objects;
	}

	/**
	 * Sets the deleted objects
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $objects The objects to be deleted
	 * @return void
	 */
	public function setDeletedObjects(Tx_Extbase_Persistence_ObjectStorage $objects) {
		$this->deletedObjects = $objects;
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
	 * 		array('blog_description LIKE ?', $keyword),
	 * 		'blogName' => 'Foo'
	 * 		)
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
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The data map
	 * @param array $conditions The conditions
	 *
	 * @return string The where part
	 */
	protected function buildQueryByConditions(Tx_Extbase_Persistence_Mapper_DataMap &$dataMap, array $conditions) {
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
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The data map
	 * @param array $propertyName The property name
	 * @param array $example The example condition
	 *
	 * @return string The where part
	 */
	protected function buildQueryByExample(Tx_Extbase_Persistence_Mapper_DataMap &$dataMap, $propertyName, $example) {
		$sql = '';
		$columnMap = $dataMap->getColumnMap($propertyName);
		if (empty($columnMap)) {
			throw new Tx_Extbase_Persistence_Exception_InvalidPropertyType("No columnMap for $propertyName", 1240305176);
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
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The data map for conversion
	 * @param string $queryPart The query part with placeholders
	 * @param array $parameters The parameters
	 *
	 * @return string The query part with replaced placeholders
	 */
	protected function replacePlaceholders(Tx_Extbase_Persistence_Mapper_DataMap &$dataMap, $queryPart, $parameters) {
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
	 * @param string $className The name of class to be fetched
	 * @param string $where WHERE statement
	 * @param string $from FROM statement will default to the tablename of the given class
	 * @param string $groupBy GROUP BY statement
	 * @param string $orderBy ORDER BY statement
	 * @param string $limit LIMIT statement
	 * @return array The matched objects
	 */
	public function fetch($className, $where = '', $from = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		if (strlen($where) === 0) {
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

		$res = $this->persistenceBackend->exec_SELECTquery(
			'*',
			$from,
			$where . $enableFields,
			$groupBy,
			$orderBy,
			$limit
			);

		$fieldMap = $this->getFieldMapFromResult($res);
		$rows = $this->getRowsFromResult($dataMap->getTableName(), $res);

		$objects = array();
		if (is_array($rows)) {
			if (count($rows) > 0) {
				$objects = $this->reconstituteObjects($dataMap, $fieldMap, $rows);
			}
		}
		return $objects;
	}

	/**
	 * Fetches and reconstitutes objects from the database by given SQL statement snippets taking a relation 
	 * table into account. The fetch process is delegated.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject The 
	 * @param Tx_Extbase_Peristence_Mapper_ColumnMap $columnMap 
	 * @param string $where The WHERE clause
	 * @param string $groupBy The GROUP BY clause
	 * @param string $orderBy The ORDER BY clause
	 * @param string $limit The LIMIT clause
	 * @param boolean $useEnableFields TRUE if enableFields() should be checked (default: TRUE) 
	 * @return array An array if matched objects
	 * @see Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper::fetch()
	 */
	public function fetchWithRelationTable(Tx_Extbase_DomainObject_AbstractEntity $parentObject, Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap, $where = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		if (strlen($where) === 0) {
			$where = '1=1';
		}
		$from = $columnMap->getChildTableName() . ', ' . $columnMap->getRelationTableName();
		$where .= ' AND ' . $columnMap->getChildTableName() . '.uid=' . $columnMap->getRelationTableName() . '.uid_foreign AND ' . $columnMap->getRelationTableName() . '.uid_local=' . t3lib_div::intval_positive($parentObject->getUid());
		return $this->fetch($columnMap->getChildClassName(), $where, $from, $groupBy, $orderBy, $limit, $useEnableFields);
	}

	protected function getFieldMapFromResult($res) {
		$fieldMap = array();
		if ($res !== FALSE) {
			$fieldPosition = 0;
			// TODO mysql_fetch_field should be available in t3lib_db (patch core)
			while ($field = mysql_fetch_field($res)) {
				$fieldMap[$field->table][$field->name] = $fieldPosition;
				$fieldPosition++;
			}
		}
		return $fieldMap;
	}

	protected function getRowsFromResult($tableName, $res) {
		$rows = array();
		while ($row = $this->persistenceBackend->sql_fetch_assoc($res)) {
			$row = $this->doLanguageAndWorkspaceOverlay($tableName, $row);
			if (is_array($row)) {
				$arrayKeys = range(0,count($row));
				array_fill_keys($arrayKeys, $row);
				$rows[] = $row;
			}
		}
		$this->persistenceBackend->sql_free_result($res);
		return $rows;
	}

	/**
	 * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
	 * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap
	 * @param array $row The row array (as reference)
	 * @param string $languageUid The language id
	 * @param string $workspaceUidUid The workspace id
	 * @return void
	 */
	protected function doLanguageAndWorkspaceOverlay($tableName, array $row, $languageUid = NULL, $workspaceUid = NULL) {
		if (!($this->pageSelectObject instanceof t3lib_pageSelect)) {
			if (TYPO3_MODE == 'FE') {
				if (is_object($GLOBALS ['TSFE'])) {
					$this->pageSelectObject = $GLOBALS ['TSFE']->sys_page;
					if ($languageUid === NULL) {
						$languageUid = $GLOBALS ['TSFE']->sys_language_content;
					}
				} else {
					require_once(PATH_t3lib . 'class.t3lib_page.php');
					$this->pageSelectObject = t3lib_div::makeInstance('t3lib_pageSelect');
					if ($languageUid === NULL) {
						$languageUid = intval(t3lib_div::_GP('L'));
					}
				}
				if ($workspaceUid !== NULL) {
					$this->pageSelectObject->versioningWorkspaceId = $workspaceUid;
				}
			} else {
				require_once(PATH_t3lib . 'class.t3lib_page.php');
				$this->pageSelectObject = t3lib_div::makeInstance( 't3lib_pageSelect' );
				//$this->pageSelectObject->versioningPreview =  TRUE;
				if ($workspaceUid === NULL) {
					$workspaceUid = $GLOBALS ['BE_USER']->workspace;
				}
				$this->pageSelectObject->versioningWorkspaceId = $workspaceUid;
			}
		}

		$this->pageSelectObject->versionOL($tableName, $row, TRUE);
		$row = $this->pageSelectObject->getRecordOverlay($tableName, $row, $languageUid, ''); //'hideNonTranslated'
		// TODO Skip if empty languageoverlay (languagevisibility)
		return $row;
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
			if ($columnMap->getTypeOfRelation() == Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
				$join .= ' LEFT JOIN ' . $columnMap->getChildTableName() . ' ON ' . $dataMap->getTableName() . '.' . $columnMap->getColumnName() . ' = ' . $columnMap->getChildTableName() . '.uid';
				$join .= $this->getJoinClause($columnMap->getChildClassName());
			}
		}
		return $join;
	}

	public function map(array $rows) {
		# code...
	}

	/**
	 * reconstitutes domain objects from $rows (array)
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The data map corresponding to the domain object
	 * @param array $fieldMap An array indexed by the table name and field name to the row index
	 * @param array $rows The rows array fetched from the database (not associative)
	 * @return array An array of reconstituted domain objects
	 */
	// TODO Check for infinite loops during reconstitution
	protected function reconstituteObjects(Tx_Extbase_Persistence_Mapper_DataMap $dataMap, array &$fieldMap, array &$rows) {
		$objects = array();
		foreach ($rows as $row) {
			$properties = $this->getProperties($dataMap, $fieldMap, $row);
			$className = $dataMap->getClassName();
			if ($this->identityMap->hasUid($className, $properties['uid'])) {
				$object = $this->identityMap->getObjectByUid($className, $properties['uid']);
			} else {
				$object = $this->reconstituteObject($dataMap->getClassName(), $properties);
				foreach ($dataMap->getColumnMaps() as $columnMap) {
					if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
						list($relatedObject) = $this->reconstituteObjects($this->getDataMap($columnMap->getChildClassName()), $fieldMap, array($row));
						$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObject);
					} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
						$where = $columnMap->getParentKeyFieldName() . '=' . intval($object->getUid());
						$relatedObjects = $this->fetch($columnMap->getChildClassName(), $where);
						$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObjects);
					} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
						$relatedObjects = $this->fetchWithRelationTable($object, $columnMap);
						$object->_reconstituteProperty($columnMap->getPropertyName(), $relatedObjects);
					}
				}
				$object->_memorizeCleanState();
				$this->identityMap->registerObject($object, $properties['uid']);
			}

			$objects[] = $object;
		}
		return $objects;
	}

	/**
	 * Returns an array of properties with the property name as key and the converted property value as value.
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The data map of the target object
	 * @param string $fieldMap the field map of the related database table.
	 * @param array $row The row to be mapped on properties
	 * @return void
	 */
	protected function getProperties(Tx_Extbase_Persistence_Mapper_DataMap $dataMap, array &$fieldMap, array &$row) {
		$properties = array();
		foreach ($dataMap->getColumnMaps() as $columnMap) {
			$properties[$columnMap->getPropertyName()] = $dataMap->convertFieldValueToPropertyValue($columnMap->getPropertyName(), $row[$columnMap->getColumnName()]);
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
	public function reconstituteObject($className, array $properties = array()) {
		// those objects will be fetched from within the __wakeup() method of the object...
		$GLOBALS['Extbase']['reconstituteObject']['properties'] = $properties;
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		unset($GLOBALS['Extbase']['reconstituteObject']);
		return $object;
	}

	/**
	 * Create a database entry for all aggregate roots first, then traverse object graph.
	 *
	 * @return void
	 */
	public function persistObjects() {
		foreach ($this->aggregateRootObjects as $object) {
			$this->persistObject($object);
		}
	}

	/**
	 * Inserts an object's corresdponding row into the database. If the object is a value object an
	 * existing instance will be looked up.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be inserted
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the property the object is stored in
	 * @return void
	 */
	protected function persistObject($object, $parentObject = NULL, $parentPropertyName = NULL, $processQueue = TRUE) {
		$queue = array();
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$properties = $object->_getProperties();

		if ($object instanceof Tx_Extbase_DomainObject_AbstractValueObject) {
			$conditions = $properties;
			unset($conditions['uid']);
			$where = $this->buildQuery($className, $conditions);
			$existingValueObjects = $this->fetch($className, $where);
			if (count($existingValueObjects) > 0) {
				$existingObject = $existingValueObjects[0];
				$object->_reconstituteProperty('uid', $existingObject->getUid());
			}
		}

		foreach ($properties as $propertyName => $propertyValue) {
			$columnMap = $dataMap->getColumnMap($propertyName);
			$columnName = $columnMap->getColumnName();
			if ($dataMap->isPersistableProperty($propertyName) && ($object->_isNew() || $object->_isDirty($propertyName))) {
				if ($columnMap->isRelation()) {
					if (($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) || ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY)) {
						$row[$columnName] = count($properties[$propertyName]);
						foreach ($propertyValue as $containedObject) {
							$queue[] = array($propertyName => $containedObject);
						}
					} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
						$queue[] = array($propertyName => $propertyValue);
					}
				} else {
					$row[$columnName] = $dataMap->convertPropertyValueToFieldValue($properties[$propertyName], FALSE);
				}
			}
		}

		if ($object->_isNew()) {
			$this->insertObject($object, $parentObject, $parentPropertyName, $row);
		} elseif ($object->_isDirty()) {
			$this->updateObject($object, $parentObject, $parentPropertyName, $row);
		}

		if ($parentObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface && !empty($parentPropertyName)) {
			$parentClassName = get_class($parentObject);
			$parentDataMap = $this->getDataMap($parentClassName);
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);

			if (($parentColumnMap->getTypeOfRelation()  === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY)) {
				$this->insertRelation($object, $parentObject, $parentPropertyName);
			}
		}

		if ($object instanceof Tx_Extbase_DomainObject_AbstractEntity) {
			$object->_memorizeCleanState();
		}
		if ($processQueue === TRUE) {
			foreach ($queue as $queuedObjects) {
				foreach($queuedObjects as $propertyName => $queuedObject) {
					$this->persistObject($queuedObject, $object, $propertyName);
				}
			}
		}

	}

	protected function insertObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, array &$row) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$tableName = $dataMap->getTableName();
		$this->addCommonFieldsToRow($object, $parentObject, $parentPropertyName, $row);
		$res = $this->persistenceBackend->exec_INSERTquery(
			$tableName,
			$row
			);
		$uid = $this->persistenceBackend->sql_insert_id();
		$object->_reconstituteProperty('uid', $uid);
		$this->referenceIndex->updateRefIndexTable($tableName, $uid);
	}

	/**
	 * Inserts relation into a relation table
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject The related object
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function insertRelation(Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$rowToInsert = array(
			'uid_local' => $parentObject->getUid(),
			'uid_foreign' => $relatedObject->getUid(),
			'tablenames' => $dataMap->getTableName(),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->persistenceBackend->exec_INSERTquery(
			$tableName,
			$rowToInsert
			);
	}

	protected function updateObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, array &$row) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$tableName = $dataMap->getTableName();
		$this->addCommonFieldsToRow($object, $parentObject, $parentPropertyName, $row);
		$uid = $object->getUid();
		$res = $this->persistenceBackend->exec_UPDATEquery(
			$tableName,
			'uid=' . intval($uid),
			$row
			);
		$this->referenceIndex->updateRefIndexTable($tableName, $uid);
	}

	/**
	 * Returns a table row to be inserted or updated in the database
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The appropriate data map representing a database table
	 * @param array $properties The properties of the object
	 * @return array A single row to be inserted in the database
	 */
	protected function addCommonFieldsToRow(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, array &$row) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		if ($dataMap->hasCreationDateColumn()) {
			$row[$dataMap->getCreationDateColumnName()] = time();
		}
		if ($dataMap->hasTimestampColumn()) {
			$row[$dataMap->getTimestampColumnName()] = time();
		}
		if ($dataMap->hasPidColumn()) {
			// FIXME check, if this really works: settings from $this->cObj must be merged into the extension settings in the dispatcher
			$row['pid'] = !empty($this->cObj->data['pages']) ? $this->cObj->data['pages'] : $GLOBALS['TSFE']->id;
		}
		if ($parentObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface && !empty($parentPropertyName)) {
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
	}

	/**
	 * Returns all property values holding child objects
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The data map
	 * @param string $properties The object properties
	 * @return array An array of properties with related child objects
	 */
	protected function getRelations(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$className = get_class($object);
		$dataMap = $this->getDataMap($className);
		$properties = $object->_getProperties();
		$relations = array();
		foreach ($dataMap->getColumnMaps() as $columnMap) {
			$propertyName = $columnMap->getPropertyName();
			if ($columnMap->isRelation()) {
				$relations[$propertyName] = $properties[$propertyName];
			}
		}
		return $relations;
	}

	/**
	 * Inserts and updates all relations of an object. It also inserts and updates data in relation tables.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function persistRelations(Tx_Extbase_DomainObject_DomainObjectInterface $object, $propertyName, array $relations) {
		$dataMap = $this->getDataMap(get_class($object));
		foreach ($relations as $propertyName => $relatedObjects) {
			if (!empty($relatedObjects)) {
				$typeOfRelation = $dataMap->getColumnMap($propertyName)->getTypeOfRelation();
				foreach ($relatedObjects as $relatedObject) {
					if ($relatedObject->_isNew()) {
						$this->persistObject($relatedObject, $object, $propertyName);
						if ($typeOfRelation === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
							$this->insertRelationInRelationTable($relatedObject, $object, $propertyName);
						}
					} elseif ($relatedObject->_isDirty()) {
						$this->persistObject($relatedObject, $object, $propertyName);
					}
				}
			}
		}
	}

	public function processDeletedObjects() {
		foreach ($this->deletedObjects as $object) {
			$this->deleteObject($object);
		}
	}

	/**
	 * Deletes an object, it's 1:n related objects, and the m:n relations in relation tables (but not the m:n related objects!)
	 *
	 * @return void
	 */
	protected function deleteObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, $recurseIntoRelations = TRUE, $onlySetDeleted = TRUE) {
		$properties = $object->_getProperties();
		$dataMap = $this->getDataMap(get_class($object));

		$tableName = $dataMap->getTableName();
		if ($onlySetDeleted === TRUE && $dataMap->hasDeletedColumn()) {
			$deletedColumnName = $dataMap->getDeletedColumnName();
			$res = $this->persistenceBackend->exec_UPDATEquery(
				$tableName,
				'uid=' . intval($object->getUid()),
				array($deletedColumnName => 1)
				);
		} else {
			$res = $this->persistenceBackend->exec_DELETEquery(
				$tableName,
				'uid=' . intval($object->getUid())
				);
		}
		$this->referenceIndex->updateRefIndexTable($tableName, $uid);
	}

	/**
	 * Deletes all relations of an object.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function deleteRelatedObjects(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $relations) {
		$dataMap = $this->getDataMap(get_class($object));
		foreach ($relations as $propertyName => $relatedObjects) {
			if (is_array($relatedObjects)) {
				foreach ($relatedObjects as $relatedObject) {
					$this->deleteObject($relatedObject, $object, $propertyName);
					if ($dataMap->getColumnMap($propertyName)->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
						$this->deleteRelationInRelationTable($relatedObject, $object, $propertyName);
					}
				}
			}
		}
	}

	/**
	 * Update relations in a relation table
	 *
	 * @param array $relatedObjects An array of related objects
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function deleteRelationInRelationTable($relatedObject, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->getDataMap(get_class($parentObject));
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->persistenceBackend->exec_SELECTquery(
			'uid_foreign',
			$tableName,
			'uid_local=' . $parentObject->getUid()
			);
		$existingRelations = array();
		while($row = $this->persistenceBackend->sql_fetch_assoc($res)) {
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
			$res = $this->persistenceBackend->exec_DELETEquery(
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
		$dataMap = $this->getDataMap($className);
		return $dataMap->isPersistableProperty($propertyName);
	}

	/**
	 * Returns a data map for a given class name
	 *
	 * @return Tx_Extbase_Persistence_Mapper_DataMap The data map
	 */
	public function getDataMap($className) {
		if (empty($this->dataMaps[$className])) {
			// TODO This is a little bit costy for table name aliases -> implement a DataMapBuilder (knowing the aliases defined in $TCA)
			$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap($className);
			$this->dataMaps[$className] = $dataMap;
		}
		return $this->dataMaps[$className];
	}

}
?>