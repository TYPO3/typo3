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

	/**
	 * Class Name of the aggregate root
	 *
	 * @var string
	 */
	protected $aggregateRootClassName;

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
	 * Constructs a new Repository
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->objects = new TX_EXTMVC_Persistence_ObjectStorage();
		$repositoryClassName = get_class($this);
		$this->session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		$this->session->registerRepository($repositoryClassName);
		if (substr($repositoryClassName,-10) == 'Repository' && substr($repositoryClassName,-11,1) != '_') {
			$this->aggregateRootClassName = substr($repositoryClassName,0,-10);
		}
		// TODO auto resolve findBy properties
		$this->allowedfindByProperties = array('name');
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
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function findAll() {
		$tableName = strtolower($this->aggregateRootClassName);
		// TODO test if table exists in db
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$tableName,
			'1=1' . t3lib_BEfunc::BEenableFields($tableName) . t3lib_BEfunc::deleteClause($tableName)
			);
		if ($res) {
			$objects = array();
			foreach ($res as $row) {
				$objects[] = $this->reconstituteBlog($row);
			}
		}
		return $objects;
	}
	
	public function __call($methodName, $attributes) {
		if (substr($methodName,0,6) === 'findBy') {
			$propertyName = TX_EXTMVC_ExtensionUtility::lowercaseFirst(substr($methodName,6));
			if (in_array($propertyName, $this->allowedfindByProperties)) {
				return $this->findByProperty($propertyName, $attributes);
			}
		}
		throw new TX_EXTMVC_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
	}
	
	private function findByProperty($propertyName, $attributes) {
		$tableName = strtolower($this->aggregateRootClassName);
		// TODO test if table exists in db
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$tableName,
			$propertyName . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($attributes[0], $tableName) . t3lib_BEfunc::BEenableFields($tableName) . t3lib_BEfunc::deleteClause($tableName)
			);
		if ($res) {
			$objects = array();
			foreach ($res as $row) {
				// TODO language and workspace overlays
				// FIXME make reconstitution of objects generic: $this->reconstitute($this->aggregateRootClassName, $row)
				$objects[] = $this->reconstituteBlog($row);
			}
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
	
	// TODO implement magic find functions for public properties

}
?>