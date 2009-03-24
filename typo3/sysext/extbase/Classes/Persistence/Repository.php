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
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
abstract class TX_EXTMVC_Persistence_Repository implements TX_EXTMVC_Persistence_RepositoryInterface, t3lib_Singleton {

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
	protected $persistenceSession;

	/**
	 * Constructs a new Repository
	 *
	 */
	public function __construct($aggregateRootClassName = NULL) {
		$this->objects = new TX_EXTMVC_Persistence_ObjectStorage();
		$repositoryClassName = get_class($this);
		$repositoryPosition = strrpos($repositoryClassName, 'Repository');
		if ($aggregateRootClassName != NULL) {
			$this->aggregateRootClassName = $aggregateRootClassName;
		} elseif (substr($repositoryClassName, -10) == 'Repository' && substr($repositoryClassName, -11, 1) != '_') {
			$this->aggregateRootClassName = substr($repositoryClassName, 0, -10);
		}
		if (empty($this->aggregateRootClassName)) {
			throw new TX_EXTMVC_Exception('The domain repository wasn\'t able to resolve the aggregate root class to manage.', 1237897039);
		}
		if (!in_array('TX_EXTMVC_DomainObject_DomainObjectInterface', class_implements($this->aggregateRootClassName))) {
			throw new TX_EXTMVC_Exception('The domain repository tried to manage objects which are not implementing the TX_EXTMVC_DomainObject_DomainObjectInterface.', 1237897039);
		}
		$this->dataMapper = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper'); // singleton
		$this->persistenceSession = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session'); // singleton
		$this->persistenceSession->registerAggregateRootClassName($this->aggregateRootClassName);
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
		$this->persistenceSession->registerAddedObject($object);
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
		$this->persistenceSession->registerRemovedObject($object);
	}

	/**
	 * Dispatches magic methods (findBy[Property]())
	 *
	 * @param string $methodName The name of the magic method
	 * @param string $arguments The arguments of the magic method
	 * @throws TX_EXTMVC_Persistence_Exception_UnsupportedMethod
	 * @return void
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 6) === 'findBy' && strlen($methodName) > 7) {
			$propertyName = TX_EXTMVC_Utility_Strings::lowercaseFirst(substr($methodName,6));
			return $this->findByProperty($propertyName, $arguments[0]);
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = TX_EXTMVC_Utility_Strings::lowercaseFirst(substr($methodName,9));
			$result = $this->findByProperty($propertyName, $arguments[0]);
			if (empty($result)) {
				return FALSE;
			} else {
				return $result[0]; // TODO Implement LIMIT
			}
		}
		throw new TX_EXTMVC_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
	}
	
	
	/**
	 * Find objects by multiple conditions. Either as SQL parts or query by example.
	 * 
	 * The following condition array would find entities with description like the given keyword and
	 * name equal to "foo".
	 *
	 * <pre>
	 * array(
	 *   array('blog_description LIKE ?', $keword),
	 *   'blogName' => 'Foo'
	 * )
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
	 */
	public function find($conditions = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {		
		return $this->dataMapper->find($this->aggregateRootClassName, $conditions, $groupBy, $orderBy, $limit, $useEnableFields);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 */
	public function findAll() {
		return $this->find();
	}

	/**
	 * Finds objects matching 'property=xyz'
	 *
	 * @param string $propertyName The name of the property (will be checked by a white list)
	 * @param string $arguments The arguments of the magic findBy method
	 * @return array The result
	 */
	protected function findByProperty($propertyName, $value) {
		return $this->find(array($propertyName => $value));
	}
}
?>