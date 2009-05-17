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
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Repository implements Tx_Extbase_Persistence_RepositoryInterface, t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Class Name of the aggregate root
	 *
	 * @var string
	 */
	protected $aggregateRootClassName;

	/**
	 * Contains the persistence session of the current extension
	 *
	 * @var Tx_Extbase_Persistence_Session
	 */
	protected $persistenceSession;

	/**
	 * Constructs a new Repository
	 *
	 */
	public function __construct($aggregateRootClassName = NULL) {
		$repositoryClassName = get_class($this);
		$repositoryPosition = strrpos($repositoryClassName, 'Repository');
		if ($aggregateRootClassName != NULL) {
			$this->aggregateRootClassName = $aggregateRootClassName;
		} elseif (substr($repositoryClassName, -10) == 'Repository' && substr($repositoryClassName, -11, 1) != '_') {
			$this->aggregateRootClassName = substr($repositoryClassName, 0, -10);
		}
		if (empty($this->aggregateRootClassName)) {
			throw new Tx_Extbase_Exception('The domain repository wasn\'t able to resolve the aggregate root class.', 1237897039);
		}
		if (!in_array('Tx_Extbase_DomainObject_DomainObjectInterface', class_implements($this->aggregateRootClassName))) {
			throw new Tx_Extbase_Exception('The domain repository tried to manage objects which are not implementing the Tx_Extbase_DomainObject_DomainObjectInterface.', 1237897039);
		}
		$this->dataMapper = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper', $GLOBALS['TYPO3_DB']); // singleton
		$this->persistenceSession = t3lib_div::makeInstance('Tx_Extbase_Persistence_Session'); // singleton
		// $this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 */
	public function add($object) {
		if (!($object instanceof $this->aggregateRootClassName)) throw new Tx_Extbase_Persistence_Exception_InvalidClass('The class "' . get_class($object) . '" is not supported by the repository.');
		$this->persistenceSession->registerAddedObject($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 */
	public function remove($object) {
		if (!($object instanceof $this->aggregateRootClassName)) throw new Tx_Extbase_Persistence_Exception_InvalidClass('The class "' . get_class($object) . '" is not supported by the repository.');
		$this->persistenceSession->registerRemovedObject($object);
	}
	
	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 */
	public function replace($existingObject, $newObject) {
		$uid = $existingObject->getUid();
		if ($uid !== NULL) {
			$this->dataMapper->replaceObject($existingObject, $newObject);
			$this->persistenceSession->unregisterReconstitutedObject($existingObject);
			$this->persistenceSession->registerReconstitutedObject($newObject);
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnknownObject('The "existing object" is unknown to the repository.', 1238068475);
		}
	}
	
	// TODO Implement Query Object
	
	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 */
	public function findAll() {
		return $this->findWhere();
	}
	
	/**
	 * Returns a query for objects of this repository
	 *
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function createQuery() {
		$type = str_replace('Repository', '', get_class($this));
		return $this->queryFactory->create($type);
	}
		
	/**
	 * Dispatches magic methods (findBy[Property]())
	 *
	 * @param string $methodName The name of the magic method
	 * @param string $arguments The arguments of the magic method
	 * @throws Tx_Extbase_Persistence_Exception_UnsupportedMethod
	 * @return void
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 6) === 'findBy' && strlen($methodName) > 7) {
			$propertyName = strtolower(substr(substr($methodName,6),0,1) ) . substr(substr($methodName,6),1);
			return $this->findByConditions(array($propertyName => $arguments[0]));
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = strtolower(substr(substr($methodName,9),0,1) ) . substr(substr($methodName,9),1);
			$result = $this->findByConditions(array($propertyName => $arguments[0]), '', '', 1);
			if (count($result) > 0) {
				return $result[0];
			} else {
				return NULL;
			}
		}
		throw new Tx_Extbase_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
	}

	/**
	 * Find objects by a raw where clause.
	 *
	 * @param string $where The conditions as an array or SQL string
	 * @param string $groupBy Group by SQL part
	 * @param string $orderBy Order by SQL part
	 * @param string $limit Limit SQL part
	 * @param bool $useEnableFields Wether to automatically restrict the query by enable fields
	 * @return array An array of objects, an empty array if no objects found
	 */
	public function findWhere($where = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		$objects = $this->dataMapper->fetch($this->aggregateRootClassName, $where, '', $groupBy, $orderBy, $limit, $useEnableFields);
		$this->persistenceSession->registerReconstitutedObjects($objects);
		return $objects;
	}

	/**
	 * Find objects by multiple conditions. Either as SQL parts or query by example.
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
	 * @param array $conditions The conditions as an array
	 * @param string $groupBy Group by SQL part
	 * @param string $orderBy Order by SQL part
	 * @param string $limit Limit SQL part
	 * @param bool $useEnableFields Wether to automatically restrict the query by enable fields
	 * @return array An array of objects, an empty array if no objects found
	 */
	public function findByConditions($conditions = '', $groupBy = '', $orderBy = '', $limit = '', $useEnableFields = TRUE) {
		$where = $this->dataMapper->buildQuery($this->aggregateRootClassName, $conditions);
		$objects = $this->dataMapper->fetch($this->aggregateRootClassName, $where, '', $groupBy, $orderBy, $limit, $useEnableFields);
		$this->persistenceSession->registerReconstitutedObjects($objects);
		return $objects;
	}

}
?>