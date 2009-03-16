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
	 * Holds an array of blacklisted properties not to be called via magic findBy methods
	 *
	 * @var array
	 */
	protected $blacklistedFindByProperties = array('passwd', 'password');
	
	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 **/
	protected $cObj;

	/**
	 * Constructs a new Repository
	 *
	 */
	public function __construct() {
		$this->objects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$repositoryClassName = get_class($this);
		if (substr($repositoryClassName, -10) == 'Repository' && substr($repositoryClassName, -11, 1) != '_') {
			$this->aggregateRootClassName = substr($repositoryClassName, 0, -10);
		}
		$this->dataMapper = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper'); // singleton
		$this->session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session'); // singleton
		$this->session->registerAggregateRootClassName($this->aggregateRootClassName);
		// FIXIT auto resolve findBy properties; black list
		$this->allowedFindByProperties = array('name', 'blog');
	}
	
	/**
	 * Sets the class name of the aggregare root
	 *
	 * @param string $aggregateRootClassName 
	 * @return void
	 */
	public function setAggregateRootClassName($aggregateRootClassName) {
		$this->aggregateRootClassName = $aggregateRootClassName;
		$this->session->registerAggregateRootClassName($this->aggregateRootClassName);
	}

	/**
	 * Returns the class name of the aggregare root
	 *
	 * @return string The class name of the aggregate root
	 */
	public function getAggregateRootClassName() {
		// TODO throw exception if not set
		return $this->aggregateRootClassName;
	}
	
	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 */
	public function add($object) {
		if (!($object instanceof $this->aggregateRootClassName)) throw new TX_EXTMVC_Persistence_Exception_InvalidClass('The class "' . get_class($object) . '" is not supported by the repository.');
		$this->objects->attach($object);
		$this->session->registerAddedObject($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 */
	public function remove($object) {
		if (!($object instanceof $this->aggregateRootClassName)) throw new TX_EXTMVC_Persistence_Exception_InvalidClass('The class "' . get_class($object) . '" is not supported by the repository.');
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
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 6) === 'findBy') {
			$propertyName = TX_EXTMVC_Utility_Strings::lowercaseFirst(substr($methodName,6));
			if (!in_array($propertyName, $this->blacklistedFindByProperties)) {
				return $this->findByProperty($propertyName, $arguments[0]);
			}
		} elseif (substr($methodName, 0, 9) === 'findOneBy') {
			$propertyName = TX_EXTMVC_Utility_Strings::lowercaseFirst(substr($methodName,9));
			if (!in_array($propertyName, $this->blacklistedFindByProperties)) {
				$result = $this->findByProperty($propertyName, $arguments[0]);
				if (empty($result)) {
					return FALSE;
				} else {
					return $result[0]; // TODO LIMIT
				}
			}
		}
		throw new TX_EXTMVC_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 */
	public function findAll() {
		// TODO implement support for SQL LIMIT
		return $this->dataMapper->findWhere($this->aggregateRootClassName);
	}
	
	/**
	 * Returns the first objects found in this repository
	 *
	 * @return TX_EXTMVC_DomainObject_AbstractDomainObject A single object, empty if no objects found
	 */
	public function findOne() {
		// TODO implement support for SQL LIMIT
		$result = $this->dataMapper->findWhere($this->aggregateRootClassName);
		return $result[0];
	}
	
	/**
	 * Finds objects matching 'property=xyz'
	 *
	 * @param string $propertyName The name of the property (will be checked by a white list)
	 * @param string $arguments The arguments of the magic findBy method
	 * @return array The result
	 */
	private function findByProperty($propertyName, $value) {
		// TODO implement support for SQL LIMIT
		if ($value instanceof TX_EXTMVC_DomainObject_AbstractDomainObject) {
			$where = $propertyName . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value->getUid(), 'foo');
		} else {
			$where = $propertyName . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, 'foo');
		}
		return $this->dataMapper->findWhere($this->aggregateRootClassName, $where);
	}
		
}
?>