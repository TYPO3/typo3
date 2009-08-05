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
 * @subpackage Persistence
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
	 * @var string
	 */
	protected $objectType;

	/**
	 * Constructs a new Repository
	 *
	 */
	public function __construct() {
		$this->persistenceManager = Tx_Extbase_Dispatcher::getPersistenceManager();
		$this->objectType = str_replace(array('_Repository_', 'Repository'), array('_Model_', ''), $this->getRepositoryClassName());
		$this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory'); // singleton
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
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
	 * @api
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
	 * @api
	 */
	public function replace($existingObject, $newObject) {
		$backend = $this->persistenceManager->getBackend();
		$session = $this->persistenceManager->getSession();
		$uid = $backend->getIdentifierByObject($existingObject);
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
	 * @api
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
	 * @api
	 */
	public function findByUid($uid) {
		if (!is_int($uid) || $uid < 0) throw new InvalidArgumentException('The uid must be a positive integer', 1245071889);
		$query = $this->createQuery();
		$result = $query->matching($query->withUid($uid))->execute();
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
	 * @param boolean $useStoragePageId If FALSE, will NOT add pid=... to the query. TRUE by default. Only change if you know what you are doing.
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function createQuery() {
		return $this->queryFactory->create($this->objectType);
	}

	/**
	 * Dispatches magic methods (findBy[Property]())
	 *
	 * @param string $methodName The name of the magic method
	 * @param string $arguments The arguments of the magic method
	 * @throws Tx_Extbase_Persistence_Exception_UnsupportedMethod
	 * @return void
	 * @api
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