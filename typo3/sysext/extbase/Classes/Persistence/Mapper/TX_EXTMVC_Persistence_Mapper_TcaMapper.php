<?php
declare(ENCODING = 'utf-8');

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
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Utility/TX_EXTMVC_Utility_Strings.php');

/**
 * A mapper to map database tables configured in $TCA on domain objects.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_Mapper_TcaMapper implements t3lib_singleton {

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
	 * A hash of table configurations from $TCA
	 *
	 * @var array
	 **/
	protected $tableConfigurations;
		
	/**
	 * Constructs a new mapper
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function __construct() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		$GLOBALS['TSFE']->includeTCA();
	}
	
	/**
	 * Finds objects matching property="xyz"
	 *
	 * @param string $propertyName The name of the property (will be chekced by a white list)
	 * @param string $arguments The WHERE statement
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function findWhere($className, $where = '1=1') {
		return $this->reconstituteObjects($className, $this->fetch($className, $where));
	}
	
	/**
	 * Fetches a rows from the database by given SQL statement snippets
	 *
	 * @param string $from FROM statement
	 * @param string $where WHERE statement
	 * @param string $groupBy GROUP BY statement
	 * @param string $orderBy ORDER BY statement
	 * @param string $limit LIMIT statement
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function fetch($className, $where = '1=1', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
		$tableName = $this->getTableName($className);
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*', // TODO limit fetched fields
			$tableName,
			$where . $this->cObj->enableFields($tableName) . $this->cObj->enableFields($tableName),
			$groupBy,
			$orderBy,
			$limit
			);
		// TODO language overlay; workspace overlay
		return $rows ? $rows : array();
	}	
	
	/**
	 * Fetches a rows from the database by given SQL statement snippets
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function fetchOneToMany($parentObject, $parentField, $tableName, $where = '', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
		$where .= ' ' . $parentField . '=' . intval($parentObject->getUid());
		return $this->fetch($tableName, $where, $groupBy, $orderBy, $limit);
	}	
	
	/**
	 * Fetches a rows from the database by given SQL statement snippets
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function fetchManyToMany($parentObject, $foreignTableName, $relationTableName, $where = '1=1', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			$foreignTableName . '.*, ' . $relationTableName . '.*',
			$foreignTableName . ' LEFT JOIN ' . $relationTableName . ' ON (' . $foreignTableName . '.uid=' . $relationTableName . '.uid_foreign)',
			$where . ' AND ' . $relationTableName . '.uid_local=' . intval($parentObject->getUid()) . $this->cObj->enableFields($foreignTableName) . $this->cObj->enableFields($foreignTableName),
			$groupBy,
			$orderBy,
			$limit
			);
		// TODO language overlay; workspace overlay
		return $rows ? $rows : array();		
	}
	
	/**
	 * Dispatches the reconstitution of a domain object to an appropriate method
	 *
	 * @param array $rows The rows array fetched from the database
	 * @throws TX_EXTMVC_Persistence_Exception_RecursionTooDeep
	 * @return array An array of reconstituted domain objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function reconstituteObjects($className, array $rows) {
		// TODO if ($depth > 10) throw new TX_EXTMVC_Persistence_Exception_RecursionTooDeep('The maximum depth of ' . $depth . ' recursions was reached.', 1233352348);	
		foreach ($rows as $row) {
			$propertiesToReconstitute = array();
			foreach ($row as $fieldName => $fieldValue) {
				$propertyName = TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($fieldName);
				$propertiesToReconstitute[$propertyName] = $this->convertFieldValueToPropertyValue($className, $propertyName, $fieldValue);
			}
			$object = $this->reconstituteObject($className, $propertiesToReconstitute);
			$properties = $object->_getProperties();
			foreach ($properties as $propertyName => $propertyValue) {
				if ($this->isOneToManyRelation($className, $propertyName)) {
					$relatedRows = $this->fetchOneToMany($object, $this->getForeignUidField($className, $propertyName), $this->getForeignTableName($className, $propertyName));
					$relatedObjects = $this->reconstituteObjects($this->getForeignClass($className, $propertyName), $relatedRows, $depth);
					$object->_reconstituteProperty($propertyName, $relatedObjects);
				} elseif ($this->isManyToManyRelation($className, $propertyName)) {
					$relatedRows = $this->fetchManyToMany($object, $this->getForeignTableName($className, $propertyName), $this->getRelationTableName($className, $propertyName));
					$relatedObjects = $this->reconstituteObjects($this->getForeignClass($className, $propertyName), $relatedRows, $depth);
					$object->_reconstituteProperty($propertyName, $relatedObjects);
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @param string $session The persistence session
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function persistAll($session) {
		$this->session = $session;
		
		// first, persit all aggregate root objects
		$aggregateRootClassNames = $this->session->getAggregateRootClassNames();
		foreach ($aggregateRootClassNames as $className) {
			$this->persistObjects($className);
		}
		
		// persist all remaining objects
		$this->persistObjects();
	}
	
	/**
	 * Persists all objects of a persitance session that are of a given class. If there
	 * is no class specified, it persits all objects of a session.
	 *
	 * @param string $className Name of the class of the objects to be persisted
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function persistObjects($className = NULL) {
		foreach ($this->session->getAddedObjects($className) as $object) {
			$this->insertObject($object);
			$this->session->unregisterAddedObject($object);
		}
		foreach ($this->session->getDirtyObjects($className) as $object) {
			$this->updateObject($object);
			$this->session->unregisterObject($object); // TODO is this necessary?
			$this->session->registerReconstitutedObject($object);
		}
		foreach ($this->session->getRemovedObjects($className) as $object) {
			$this->deleteObject($object);
			$this->session->unregisterRemovedObject($object);
		}	
	}
	
	/**
	 * Inserts an object to the database.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function insertObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL) {
		$queuedRelations = array();
		$rowToInsert = array();
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if ($this->isOneToManyRelation(get_class($object), $propertyName)) {
				$queuedRelations = t3lib_div::array_merge_recursive_overrule($queuedRelations, array($propertyName => $propertyValue));
				$rowToInsert[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = count($properties[$propertyName]);
			} elseif ($this->isManyToManyRelation(get_class($object), $propertyName)) {
				$queuedRelations = t3lib_div::array_merge_recursive_overrule($queuedRelations, array($propertyName => $propertyValue));
				$rowToInsert[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = count($properties[$propertyName]);
			} else {
				$rowToInsert[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = $this->convertPropertyValueToFieldValue($propertyValue);
			}
		}
		
		$rowToInsert['pid'] = !empty($this->cObj->data['pages']) ? $this->cObj->data['pages'] : $GLOBALS['TSFE']->id;
		$rowToInsert['tstamp'] = time();
		if ($parentObject !== NULL && $parentPropertyName !== NULL) {
			$foreignUidfield = $this->getForeignUidField(get_class($parentObject), $parentPropertyName);
			if ($foreignUidfield !== NULL) {
				$rowToInsert[$foreignUidfield] = $parentObject->getUid();
			}
			$foreignTablefield = $this->getForeignTableField(get_class($parentObject), $parentPropertyName);
			if ($foreignTablefield !== NULL) {
				$rowToInsert[$foreignTablefield] = $this->getTableName(get_class($parentObject));
			}
		}
		$tableName = $this->getTableName(get_class($object));
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$tableName,
			$rowToInsert
			);

		$object->_reconstituteProperty('uid', $GLOBALS['TYPO3_DB']->sql_insert_id());
		// var_dump($object);
		
		foreach ($queuedRelations as $propertyName => $relatedObjects) {
			foreach ($relatedObjects as $relatedObject) {
				if (!$this->session->isReconstitutedObject($relatedObject)) {
					$this->insertObject($relatedObject, $object, $propertyName);
					if ($this->isManyToManyRelation(get_class($object), $propertyName)) {
						$this->insertRelation($object, $propertyName, $relatedObject);
					}
				}
			}
		}
		
	}
	
	/**
	 * Updates a modified object in the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function updateObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL) {
		$queuedRelations = array();
		$fieldsToUpdate = array();
		$properties = $object->_getDirtyProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if ($this->isOneToManyRelation(get_class($object), $propertyName)) {
				$queuedRelations = t3lib_div::array_merge_recursive_overrule($queuedRelations, array($propertyName => $propertyValue));
				$fieldsToUpdate[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = count($properties[$propertyName]);
			} elseif ($this->isManyToManyRelation(get_class($object), $propertyName)) {
				$queuedRelations = t3lib_div::array_merge_recursive_overrule($queuedRelations, array($propertyName => $propertyValue));
				$fieldsToUpdate[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = count($properties[$propertyName]);
			} else {
				$fieldsToUpdate[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = $this->convertPropertyValueToFieldValue($propertyValue);
			}
		}
		
		$fieldsToUpdate['crdate'] = time();
		if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
			$fieldsToUpdate['cuser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
		if ($parentObject !== NULL && $parentPropertyName !== NULL) {
			$foreignUidfield = $this->getForeignUidField(get_class($parentObject), $parentPropertyName);
			if ($foreignUidfield !== NULL) {
				$fieldsToUpdate[$foreignUidfield] = $parentObject->getUid();
			}
			$foreignTablefield = $this->getForeignTableField(get_class($parentObject), $parentPropertyName);
			if ($foreignTablefield !== NULL) {
				$fieldsToUpdate[$foreignTablefield] = $this->getTableName(get_class($parentObject));
			}
		}
		$tableName = $this->getTableName(get_class($object));
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$tableName,
			'uid=' . $object->getUid(),
			$fieldsToUpdate
			);

		// var_dump($object);

		foreach ($queuedRelations as $propertyName => $relatedObjects) {
			foreach ($relatedObjects as $relatedObject) {
				if (!$this->session->isReconstitutedObject($relatedObject)) {
					$this->insertObject($relatedObject, $object, $propertyName);
					if ($this->isManyToManyRelation(get_class($object), $propertyName)) {
						$this->insertRelation($object, $propertyName, $relatedObject);
					}
				}
			}
		}
		
	}

	/**
	 * Deletes an object, it's 1:n related objects, and the m:n relations in relation tables (but not the m:n related objects!)
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function deleteObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $parentObject = NULL, $parentPropertyName = NULL) {
		$queuedRelations = array();
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if ($this->isOneToManyRelation(get_class($object), $propertyName)) {
				$queuedRelations = t3lib_div::array_merge_recursive_overrule($queuedRelations, array($propertyName => $propertyValue));
			} elseif ($this->isManyToManyRelation(get_class($object), $propertyName)) {
				$queuedRelations = t3lib_div::array_merge_recursive_overrule($queuedRelations, array($propertyName => $propertyValue));
			}
		}
		
		$tableName = $this->getTableName(get_class($object));
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$tableName,
			'uid=' . $object->getUid()
			);

		foreach ($queuedRelations as $propertyName => $relatedObjects) {
			foreach ($relatedObjects as $relatedObject) {
				if ($this->session->isReconstitutedObject($relatedObject)) {
					if ($this->isOneToManyRelation(get_class($object), $propertyName)) {
						$this->deleteObject($relatedObject, $object, $propertyName);
					} elseif ($this->isManyToManyRelation(get_class($object), $propertyName)) {
						$this->deleteRelations($object, $propertyName, $relatedObject);
					}
					
				}
			}
		}
		
	}
	
	/**
	 * Inserts relation to a relation table
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in 
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $relatedObject The related object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function insertRelation(TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject, $parentPropertyName, TX_EXTMVC_DomainObject_AbstractDomainObject $relatedObject) {
		$rowToInsert = array(
			'uid_local' => $parentObject->getUid(),
			'uid_foreign' => $relatedObject->getUid(),
			'tablenames' => $this->getTableName(get_class($parentObject)),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$tableName = $this->getRelationTableName(get_class($parentObject), $parentPropertyName);
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$tableName,
			$rowToInsert
			);
	}
	
	/**
	 * Inserts relation to a relation table
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in 
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $relatedObject The related object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function deleteRelations(TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject, $parentPropertyName, TX_EXTMVC_DomainObject_AbstractDomainObject $relatedObject) {
		$tableName = $this->getRelationTableName(get_class($parentObject), $parentPropertyName);
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$tableName,
			'uid_local=' . $parentObject->getUid()
			);
	}
	
	/**
	 * Returns all columns configured in $TCA for a given table
	 *
	 * @param string $tableName The table name 
	 * @return array The column configurations from $TCA
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function getColumns($tableName) {
		if (empty($this->tableConfigurations[$tableName])) {
			t3lib_div::loadTCA($tableName);
			$this->tableConfigurations[$tableName] = $GLOBALS['TCA'][$tableName];
		}
		return $this->tableConfigurations[$tableName]['columns'];
	}
	
	/**
	 * Returns a table name for a given class
	 *
	 * @param string $className The class name
	 * @return string The table name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function getTableName($className) {
		// TODO implement table name aliases
		return strtolower($className);
	}

	/**
	 * Returns the name of a column indicating the 'deleted' state of the row
	 *
	 * @param string $className The class name
	 * @return string The class name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */	
	protected function getDeletedColumnName($className) {
		$this->getTableName($className);
		return $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
	}
	
	/**
	 * Returns the name of a column indicating the 'hidden' state of the row
	 *
	 * @param string $className The class name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */	
	protected function getHiddenColumnName($className) {;
		$this->getTableName($className);
		return $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'];
	}

	/**
	 * Returns TRUE if the given property corresponds to one to many relation in the database
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the given property corresponds to one to many relation in the database
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function isOneToManyRelation($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		$columnConfiguration = $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config'];
		if (array_key_exists('foreign_table', $columnConfiguration) && !array_key_exists('MM', $columnConfiguration)) return TRUE;
		return FALSE;
	}
	
	/**
	 * Returns TRUE if the given property corresponds to many to many relation in the database
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the given property corresponds to many to many relation in the database
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function isManyToManyRelation($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		$columnConfiguration = $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config'];
		if (array_key_exists('foreign_table', $columnConfiguration) && array_key_exists('MM', $columnConfiguration)) return TRUE;
		return FALSE;
	}
	
	/**
	 * Returns the foreign class name for a given parent class and property
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return string The foreign class name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function getForeignClass($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['foreign_class'];
	}
	
	/**
	 * Returns the foreign table name for a given parent class and property
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return string The foreign table name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function getForeignTableName($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['foreign_table'];
	}
	
	/**
	 * Returns the foreign uid field name for a given parent class and property
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return string The foreign uid field name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function getForeignUidField($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['foreign_field'];
	}
	
	/**
	 * Returns the foreign table field name for a given parent class and property
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return string The foreign table field name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function getForeignTableField($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['foreign_table_field'];
	}
	
	/**
	 * Returns the relation table name for a given parent class and property
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return string The relation table name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function getRelationTableName($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['MM'];
	}
		
	/**
	 * Returns TRUE if the property of a given class is of type date (as configured in $TCA)
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property of a given class is of type date (as configured in $TCA)
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function isOfTypeDate($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return strpos($columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['eval'], 'date') !== FALSE
			|| strpos($columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['eval'], 'datetime') !== FALSE;
	}
	
	/**
	 * Returns TRUE if the property of a given class is of type boolean (as configured in $TCA)
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property of a given class is of type boolean (as configured in $TCA)
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function isOfTypeBoolean($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		return $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['type'] === 'check' 
			&& empty($columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config']['items']);
	}
	
	/**
	 * Returns TRUE if the property is persistable (configured in $TCA)
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property is persistable (configured in $TCA)
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	public function isPersistableProperty($className, $propertyName) {
		$columns = $this->getColumns($this->getTableName($className));
		if (array_key_exists(TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName), $columns)) return TRUE;
		return FALSE;
	}
	
	/**
	 * Converts a value from a database field type to a property type
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @param mixed $fieldValue The field value
	 * @return mixed The converted value
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function convertFieldValueToPropertyValue($className, $propertyName, $fieldValue) {
		if ($this->isOfTypeDate($className, $propertyName)) {
			$convertedValue = new DateTime(strftime('%Y-%m-%d %H:%M', $fieldValue), new DateTimeZone('UTC'));
		} elseif ($this->isOfTypeBoolean($className, $propertyName)) {
			if ($fieldValue === '0') {
				$convertedValue = FALSE;
			} else {
				$convertedValue = TRUE;
			}
		} else {
			$convertedValue = $fieldValue;
		}
		return $convertedValue;
	}
	
	/**
	 * Converts a value from a property type to a database field type
	 *
	 * @param mixed $propertyValue The property value
	 * @return mixed The converted value
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */		
	protected function convertPropertyValueToFieldValue($propertyValue) {
		if ($propertyValue instanceof DateTime) {
			$convertedValue = $propertyValue->format('U');
		} elseif (is_bool($propertyValue)) {
			$convertedValue = $propertyValue ? 1 : 0;
		} else {
			$convertedValue = $propertyValue;
		}
		return $convertedValue;
	}
	
}
?>