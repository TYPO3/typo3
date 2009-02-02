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
	 * Constructs a new mapper
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function __construct() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$GLOBALS['TSFE']->includeTCA();
	}
		
	/**
	 * Returns all objects of the given class name
	 *
	 * @return array An array of objects, empty if no objects found
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function loadAll($className) {
		return $this->reconstituteObjects($this->fetch($this->getTableName($className)));
	}
	
	/**
	 * Finds objects matching 'property=xyz'
	 *
	 * @param string $propertyName The name of the property (will be chekced by a white list)
	 * @param string $arguments The arguments of the magic findBy method
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function loadWhere($className, $propertyName, $arguments) {
		$tableName = $this->getTableName($className);
		$where = $propertyName . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($arguments[0], $tableName);
		return $this->reconstituteObjects($className, $this->fetch($tableName, $where));
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
	private function fetch($tableName, $where = '1=1', $groupBy = NULL, $orderBy = NULL, $limit = NULL) {
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
			foreach ($object->getOneToManyRelations() as $propertyName => $tcaColumnConfiguration) {
				$relatedRows = $this->fetchOneToMany($object, $tcaColumnConfiguration['foreign_field'], $tcaColumnConfiguration['foreign_table']);
				$relatedObjects = $this->reconstituteObjects($relatedRows, $tcaColumnConfiguration['foreign_class'], $depth++);
				$object->_reconstituteProperty($propertyName, $relatedObjects);
			}
			foreach ($object->getManyToManyRelations() as $propertyName => $tcaColumnConfiguration) {
				$relatedRows = $this->fetchManyToMany($object, $tcaColumnConfiguration['foreign_table'], $tcaColumnConfiguration['MM']);
				$relatedObjects = $this->reconstituteObjects($relatedRows, $tcaColumnConfiguration['foreign_class'], $depth++);
				$object->_reconstituteProperty($propertyName, $relatedObjects);
			}
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
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function reconstituteObject($className, array $properties = array()) {
		// those objects will be fetched from within the __wakeup() method of the object...
		$GLOBALS['EXTMVC']['reconstituteObject']['properties'] = $properties;
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		unset($GLOBALS['EXTMVC']['reconstituteObject']);
		return $object;
	}	

	/**
	 * Inserts an object in the database.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function insert(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		$row = array(
			'pid' => 0, // FIXME
			'tstamp' => time(),
			'crdate' => time(),
			// FIXME static fields
			'name' => $object->getName(),
			'description' => $object->getDescription()
			// 'logo' => $object->getLogo(),
			);
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$this->getTableName($this->getClassName($object)),
			$row
			);
	}
	
	/**
	 * Updates an object
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function update(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {

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
	
	public function getColumns(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		t3lib_div::loadTCA($this->getTableName($this->getClassName($object)));
		return $GLOBALS['TCA'][$this->getTableName($this->getClassName($object))]['columns'];
	}
		
	protected function getClassName(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		return get_class($object);
	}
	
	protected function getTableName($className) {
		return strtolower($className);
	}
	
	protected function getDeletedColumnName($tableName) {
		return $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
	}
	
	protected function getHiddenColumnName($tableName) {
		return $GLOBALS['TCA'][$tableNAme]['ctrl']['enablecolumns']['disabled'];
	}
	
	public function isPersistable(TX_EXTMVC_DomainObject_AbstractDomainObject $object, $propertyName) {
		$columns = $this->getColumns($object);
		foreach ($columns as $columnName => $columnConfiguration) {
			if (TX_EXTMVC_Utility_Strings::camelCaseToLowerCaseUnderscored($columnName) == $propertyName) return TRUE;
		}
		return FALSE;
	}
	
}
?>