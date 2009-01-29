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
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_ExtensionUtility.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_ObjectStorage.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_RepositoryInterface.php');

/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_Repository implements TX_EXTMVC_Persistence_RepositoryInterface, t3lib_Singleton {

// TODO make abstract

	/**
	 * Class Name of the aggregate root
	 *
	 * @var string
	 */
	protected $aggregateRootClassName;

	/**
	 * Table name of the aggregate root
	 *
	 * @var string
	 */
	protected $tableName;

	/**
	 * Objects of this repository
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $objects;

	/**
	 * Contains the persistence session of the current extension
	 *
	 * @var TX_EXTMVC_Persistence_Session
	 */
	protected $session;

	/**
	 * Holds an array of allowed properties to be called via magig findBy methods
	 *
	 * @var array
	 */
	protected $findBy = array();

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 **/
	protected $cObj;

	/**
	 * Constructs a new Repository
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->objects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$repositoryClassName = get_class($this);
		$this->session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		$this->session->registerRepository($repositoryClassName);
		if (substr($repositoryClassName,-10) == 'Repository' && substr($repositoryClassName,-11,1) != '_') {
			$this->aggregateRootClassName = substr($repositoryClassName,0,-10);
		}
		// TODO check if the table exists in the database
		$this->tableName = strtolower($this->aggregateRootClassName);
		// TODO auto resolve findBy properties
		$this->allowedFindByProperties = array('name');
	}
	
	/**
	 * Sets the class name of the aggregare root
	 *
	 * @param string $aggregateRootClassName 
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function setAggregateRootClassName($aggregateRootClassName) {
		$this->aggregateRootClassName = $aggregateRootClassName;
	}

	/**
	 * Returns the class name of the aggregare root
	 *
	 * @return string The class name of the aggregate root
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getAggregateRootClassName() {
		return $this->aggregateRootClassName;
	}
	
	/**
	 * Sets the database table name for the aggregare root
	 *
	 * @param string $tableName The table name for the aggregate root
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * Returns the database table name for the aggregare root
	 *
	 * @return string The table name for the aggregate root
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function add($object) {
		$this->objects->attach($object);
		$this->session->registerAddedObject($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function remove($object) {
		$this->objects->detach($object);
		$this->session->registerRemovedObject($object);
	}

	/**
	 * Dispatches magic methods (findByProperty())
	 *
	 * @param string $methodName The name of the magic method
	 * @param string $arguments The arguments of the magic method
	 * @throws TX_EXTMVC_Persistence_Exception_UnsupportedMethod
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 6) === 'findBy') {
			$propertyName = TX_EXTMVC_ExtensionUtility::lowercaseFirst(substr($methodName,6));
			if (in_array($propertyName, $this->allowedFindByProperties)) {
				return $this->findByProperty($propertyName, $arguments);
			}
		}
		throw new TX_EXTMVC_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function findAll() {
		return $this->reconstituteObjects($this->fetchFromDatabase());
	}
	
	/**
	 * Finds objects matching 'property=xyz'
	 *
	 * @param string $propertyName The name of the property (will be chekced by a white list)
	 * @param string $arguments The arguments of the magic findBy method
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	private function findByProperty($propertyName, $arguments) {
		$where = $propertyName . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($arguments[0], $this->tableName);
		return $this->reconstituteObjects($this->fetchFromDatabase($where));
	}
	
	/**
	 * Fetches a reasult set by given SQL statement snippets
	 *
	 * @param string $where WHERE statement
	 * @param string $groupBy GROUP BY statement
	 * @param string $orderBy ORDER BY statement
	 * @param string $limit LIMIT statement
	 * @param string $tableName The table name
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	private function fetchFromDatabase($where = '1=1', $groupBy = '', $orderBy = '', $limit = '', $tableName = NULL) {
		$tableName = $tableName === NULL ? $this->tableName : $tableName;
		$resultSet = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*', // TODO limit fetched fields
			$tableName,
			$where . $this->cObj->enableFields($this->tableName) . $this->cObj->enableFields($this->tableName),
			$groupBy,
			$orderBy,
			$limit
			);
		// TODO language overlay; workspace overlay
		return $resultSet ? $resultSet : array();
	}
	
	/**
	 * Dispatches the reconstitution of a domain object to an appropriate method
	 *
	 * @param string $resultSet The result set fetched from the database
	 * @throws TX_EXTMVC_Persistence_Exception_UnsupportedMethod
	 * @return array An array of reconstituted domain objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function reconstituteObjects($resultSet) {
		$reconstituteMethodName = 'reconstitute' . array_pop(explode('_', $this->aggregateRootClassName));
		if (!method_exists($this, $reconstituteMethodName)) throw new TX_EXTMVC_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
		$objects = array();
		foreach ($resultSet as $row) {
			$objects[] = $this->$reconstituteMethodName($row);
		}
		return $objects;
	}
	
	/**
	 * Persists changes (added, removed or changed objects) to the database.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function persistAll() {
		$this->deleteRemoved();
		$this->insertAdded();
		$this->updateDirty();
	}
	
	/**
	 * Deletes all removed objects from the database. 
	 * This is only a template method to be overwritten in extending classes.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function deleteRemoved() {
	}
	
	/**
	 * Inserts all newly created objects to the database. 
	 * This is only a template method to be overwritten in extending classes.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function insertAdded() {
	}
	
	/**
	 * Updates all modified objects. 
	 * This is only a template method to be overwritten in extending classes.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function updateDirty() {
	}
	
	
}
?>