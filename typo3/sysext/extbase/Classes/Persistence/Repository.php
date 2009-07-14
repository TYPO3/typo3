<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Constructs a new Repository
	 *
	 */
	public function __construct() {
		$this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
		$this->persistenceManager = t3lib_div::makeInstance('Tx_Extbase_Persistence_Manager'); // singleton; must have been initialized before
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 */
	public function add($object) {
	// SK: Why is this commented out?
//		if (!is_object($object) || !($object instanceof $this->aggregateRootClassName)) throw new Tx_Extbase_Persistence_Exception_InvalidClass('The class "' . get_class($object) . '" is not supported by the repository.');
		$this->persistenceManager->getSession()->registerAddedObject($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 */
	public function remove($object) {

	// SK: Why is this commented out?
//		if (!is_object($object) || !($object instanceof $this->aggregateRootClassName)) throw new Tx_Extbase_Persistence_Exception_InvalidClass('The class "' . get_class($object) . '" is not supported by the repository.');
		$this->persistenceManager->getSession()->registerRemovedObject($object);
	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * return void
	 */
	public function replace($existingObject, $newObject) {
		$backend = $this->persistenceManager->getBackend();
		$session = $this->persistenceManager->getSession();
		$uid = $backend->getUidByObject($existingObject);
		if ($uid !== NULL) {
			$backend->replaceObject($existingObject, $newObject);
			$session->unregisterReconstitutedObject($existingObject);
			$session->registerReconstitutedObject($newObject);
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnknownObject('The "existing object" is unknown to the repository.', 1238068475);
		}
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 */
	public function findAll() {
		$result = $this->createQuery()->execute();
		$this->persistenceManager->getSession()->registerReconstitutedObjects($result);
		return $result;
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param int $uid The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 */
	public function findByUid($uid) {
		if (!is_int($uid) || $uid < 0) throw new InvalidArgumentException('The uid must be a positive integer', 1245071889);
		$query = $this->createQuery();
		$result = $query->matching($query->withUid($uid))
			->setLimit(1)
			->execute();
		$object = NULL;
		if (count($result) > 0) {
			$object = current($result);
			$this->persistenceManager->getSession()->registerReconstitutedObject($object);
		}
		return $object;
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function createQuery() {
		$repositoryClassName = $this->getRepositoryClassName();
		if (substr($repositoryClassName, -10) == 'Repository' && substr($repositoryClassName, -11, 1) != '_') {
			$type = substr($repositoryClassName, 0, -10);
		} else {
			throw new Tx_Extbase_Exception('The domain repository wasn\'t able to resolve the target class name.', 1237897039);
		}
		if (!in_array('Tx_Extbase_DomainObject_DomainObjectInterface', class_implements($type))) {
			throw new Tx_Extbase_Exception('The domain repository tried to manage objects which are not implementing the Tx_Extbase_DomainObject_DomainObjectInterface.', 1237897039);
		}
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
			$propertyName = strtolower(substr(substr($methodName, 6), 0, 1) ) . substr(substr($methodName, 6), 1);
			$query = $this->createQuery();
			$result = $query->matching($query->equals($propertyName, $arguments[0]))
				->execute();
			$this->persistenceManager->getSession()->registerReconstitutedObjects($result);
			return $result;
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = strtolower(substr(substr($methodName, 9), 0, 1) ) . substr(substr($methodName, 9), 1);
			$query = $this->createQuery();
			$result = $query->matching($query->equals($propertyName, $arguments[0]))
				->setLimit(1)
				->execute();
			$object = NULL;
			if (count($result) > 0) {
				$object = current($result);
				// SK: registerReconstitutedObject() needs to be called automatically inside the DataMapper!
				$this->persistenceManager->getSession()->registerReconstitutedObject($object);
			}
			return $object;
		}
		throw new Tx_Extbase_Persistence_Exception_UnsupportedMethod('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
	}

	/**
	 * Returns the class name of this class.
	 *
	 * @return string Class name of the repository.
	 */
	protected function getRepositoryClassName() {
		return get_class($this);
	}

}
?>