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
 * A mapper to map database tables configured in $TCA onto domain objects.
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
	 * @var 
	 **/
	protected $session;
		
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
	private function fetch($className, $where = '1=1', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
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
	private function fetchOneToMany($parentObject, $parentField, $tableName, $where = '', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
		$where .= ' ' . $parentField . '=' . intval($parentObject->getUid());
		return $this->fetch($tableName, $where, $groupBy, $orderBy, $limit);
	}	
	
	/**
	 * Fetches a rows from the database by given SQL statement snippets
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	private function fetchManyToMany($parentObject, $foreignTableName, $relationTableName, $where = '1=1', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
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
	protected function reconstituteObjects($className, array $rows, $depth = 0) {
		if ($depth > 10) throw new TX_EXTMVC_Persistence_Exception_RecursionTooDeep('The maximum depth of ' . $depth . ' recursions was reached.', 1233352348);
		foreach ($rows as $row) {
			$object = $this->reconstituteObject($className, $row);
			foreach ($this->getOneToManyRelations($className) as $propertyName => $tcaColumnConfiguration) {
				$relatedRows = $this->fetchOneToMany($object, $tcaColumnConfiguration['foreign_field'], $tcaColumnConfiguration['foreign_table']);
				$relatedObjects = $this->reconstituteObjects($tcaColumnConfiguration['foreign_class'], $relatedRows, ++$depth);
				$object->_reconstituteProperty($propertyName, $relatedObjects);
			}
			foreach ($this->getManyToManyRelations($className) as $propertyName => $tcaColumnConfiguration) {
				$relatedRows = $this->fetchManyToMany($object, $tcaColumnConfiguration['foreign_table'], $tcaColumnConfiguration['MM']);
				$relatedObjects = $this->reconstituteObjects($tcaColumnConfiguration['foreign_class'], $relatedRows, ++$depth);
				$object->_reconstituteProperty($propertyName, $relatedObjects);
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
	
	public function persistAll($session) {
		$this->session = $session;
		$this->persistAggregateRoots();

		foreach ($this->session->getRemovedObjects() as $object) {
			$this->delete($object);
			$this->session->unregisterRemovedObject($object);
		}

		$this->save();
	}
	
	/**
	 * Traverse all aggregate roots breadth first.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function persistAggregateRoots() {
		$aggregateRootClassNames = $this->session->getAggregateRootClassNames();
		// make sure we have a corresponding node for all new objects on
		// first level
		foreach ($aggregateRootClassNames as $className) {
			$addedObjects = $this->session->getAddedObjects($className);
			foreach ($addedObjects as $object) {
				$this->persistObject($object);
				$this->session->unregisterAddedObject($object);
			}
		}

		// // now traverse into the objects
		// foreach ($aggregateRootClassNames as $object) {
		// 	$this->persistObject($object);
		// }

	}
	
	/**
	 * Persists an object to the database.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function persistObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		$queue = array();
		$row = array(
			'pid' => 0, // FIXME
			'tstamp' => time(),
			);
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if ($this->isPersistable(get_class($object), $propertyName)) {
				if ($this->isRelation(get_class($object), $propertyName)) {
					if (!$this->session->isReconstitutedObject($object) || $this->session->isDirtyObject($object)) {
						$this->persistArray($object, $propertyName, $propertyValue, $queue);
						$row[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = count($properties[$propertyName]);
					} else {
						$queue = array_merge($queue, array_values($propertyValue));
					}
				} elseif (is_array($propertyValue)) {
					$this->persistArray($object, $propertyName, $propertyValue, $queue);
				} elseif ($propertyValue instanceof TX_EXTMVC_DomainObject_AbstractDomainObject) {
					if (!$this->session->isReconstitutedObject($object)) {
						$this->persistObject($propertyValue);
					}
					$queue[] = $propertyValue;
				} else {
					// TODO Property Mapper
					$row[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = $propertyValue;
				}
			}
		}
		
		$tableName = $this->getTableName(get_class($object));
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$tableName,
			$row
			);
		
		$object->_reconstituteProperty('uid', $GLOBALS['TYPO3_DB']->sql_insert_id());
		$this->session->unregisterObject($object);
		$this->session->registerReconstitutedObject($object);
		// var_dump($object);
		
		// here we loop over the objects. their nodes are already at the
		// right place and have the right name. fancy, eh?
		foreach ($queue as $object) {
			$this->persistObject($object);
		}
	}
	
	/**
	 * Store an array as a node of type flow3:arrayPropertyProxy, with each
	 * array element becoming a property named like the key and the value.
	 *
	 * Every element not being an object or array will become a property on the
	 * node, arrays will be handled recursively.
	 *
	 * Note: Objects contained in the array will have a node created, properties
	 * On those nodes must be set elsewhere!
	 *
	 * @param array $array The array for which to create a node
	 * @param \F3\PHPCR\NodeInterface $parentNode The node to add the property proxy to
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @param array &$queue Found entities are accumulated here.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function persistArray(TX_EXTMVC_DomainObject_AbstractDomainObject $parentObject, $propertyName, array $array, array &$queue) {
		foreach ($array as $key => $element) {
			if ($element instanceof TX_EXTMVC_DomainObject_AbstractDomainObject) {
				if (!$this->session->isReconstitutedObject($element) || $this->session->isDirtyObject($element)) {
					$this->persistObject($element);
				}
			} elseif (is_array($element)) {
				$this->persistArray($parentObject, $propertyName, $element, $queue);
			} else {
				$queue[] = $element;
			}
			// TODO persist arrays with plain values

		}
	}
	
	/**
	 * Deletes all removed objects from the database.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function processRemovedObject($object) {
	}
	
	/**
	 * Updates an object
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function update(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $depth = 0) {
		if ($depth > 10) throw new TX_EXTMVC_Persistence_Exception_RecursionTooDeep('The maximum depth of ' . $depth . ' recursions was reached.', 1233352348);
		$row = array(
			'tstamp' => time(),
			);
		$properties = $object->_getProperties();
		$columns = $this->getColumns($this->getClassName($object));
		$relations = $this->getRelations($this->getClassName($object));
		foreach ($relations as $propertyName => $tcaColumnConfiguration) {
			foreach ($properties[$propertyName] as $object) {
				// TODO implement reverse update chain
				if (TRUE || $object->_isDirty()) {
					$this->update($object, ++$depth);
				}
			}
			$row[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = count($properties[$propertyName]);
			unset($properties[$propertyName]);
		}
		foreach ($properties as $propertyName => $propertyValue) {
			$row[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)] = $propertyValue;
		}
		$uid = $object->getUid();
		// debug($uid);
		// debug($row);
		// $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
		// 	$this->getTableName($this->getClassName($object)),
		// 	'uid=' . $object->getUid(),
		// 	$row
		// 	);
	}
	
	/**
	 * Deletes an object
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function delete(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $onlyMarkAsDeleted = TRUE) {
		$tableName = $this->getTableName($this->getClassName($object));
		if ($onlyMarkAsDeleted) {
			$deletedColumnName = $this->getDeletedColumnName($tableName);
			if (empty($deletedColumnName)) throw new Exception('Could not mark object as deleted in table "' . $tableName . '"');
	        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				$this->getTableName($object),
				'uid = ' . intval($object->getUid()),
				array($deletedColumnName => 1)
				);
		} else {
			// TODO remove associated objects
			
			$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$this->getTableName($object),
				'uid=' . intval($object->getUid())
				);
		}
	}
	
	protected function getColumns($className) {
		$tableName = $this->getTableName($className);
		t3lib_div::loadTCA($tableName);
		return $GLOBALS['TCA'][$tableName]['columns'];
	}
		
	protected function getClassName(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		return get_class($object);
	}
	
	protected function getTableName($className) {
		// TODO implement table name aliases
		return strtolower($className);
	}
	
	protected function getDeletedColumnName($className) {
		$this->getTableName($className);
		return $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
	}
	
	protected function getHiddenColumnName($className) {;
		$this->getTableName($className);
		return $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'];
	}
	
	protected function getRelations($className) {
		return t3lib_div::array_merge_recursive_overrule($this->getOneToManyRelations($className), $this->getManyToManyRelations($className));
	}
	
	protected function isRelation($className, $propertyName) {
		$columns = $this->getColumns($className);		
		if (array_key_exists('foreign_table', $columns[TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName)]['config'])) return TRUE;
		return FALSE;
	}
	
	protected function getOneToManyRelations($className) {
		$columns = $this->getColumns($className);
		$oneToManyRelations = array();
		foreach ($columns as $columnName => $columnConfiguration) {
			$propertyName = TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($columnName);
			if (array_key_exists('foreign_table', $columnConfiguration['config'])) {
				// TODO take IRRE into account
				if (!array_key_exists('MM', $columnConfiguration['config'])) {
					// TODO implement a $TCA object 
					$oneToManyRelations[$propertyName] = array(
						'foreign_class' => $columnConfiguration['config']['foreign_class'],
						'foreign_table' => $columnConfiguration['config']['foreign_table'],
						'foreign_field' => $columnConfiguration['config']['foreign_field'],
						'foreign_table_field' => $columnConfiguration['config']['foreign_table_field']
						);
				}
			}				
		}
		return $oneToManyRelations;
	}
	
	protected function getManyToManyRelations($className) {
		$columns = $this->getColumns($className);
		$relations = array();
		foreach ($columns as $columnName => $columnConfiguration) {
			$propertyName = TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($columnName);
			if (array_key_exists('foreign_table', $columnConfiguration['config'])) {
				// TODO take IRRE into account
				if (array_key_exists('MM', $columnConfiguration['config'])) {
					// TODO implement a $TCA object 
					$relations[$propertyName] = array(
						'foreign_class' => $columnConfiguration['config']['foreign_class'],
						'foreign_table' => $columnConfiguration['config']['foreign_table'],
						'MM' => $columnConfiguration['config']['MM']
						);
				}
			}				
		}
		return $relations;
	}
	
	public function isPersistable($className, $propertyName) {
		$columns = $this->getColumns($className);
		if (array_key_exists(TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($propertyName), $columns)) return TRUE;
		return FALSE;
	}
	
}
?>