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
 * @api
 */
class Tx_Extbase_Persistence_Repository implements Tx_Extbase_Persistence_RepositoryInterface, t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 **/
	protected $identityMap;

	/**
	 * Objects of this repository
	 *
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $addedObjects;

	/**
	 * Objects removed but not found in $this->addedObjects at removal time
	 *
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var Tx_Extbase_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $objectType;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array();

	/**
	 * @var Tx_Extbase_Persistence_QuerySettingsInterface
	 */
	protected $defaultQuerySettings = NULL;

	/**
	 * Constructs a new Repository
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 */
	public function __construct(Tx_Extbase_Object_ObjectManagerInterface $objectManager = NULL) {
		$this->addedObjects = new Tx_Extbase_Persistence_ObjectStorage();
		$this->removedObjects = new Tx_Extbase_Persistence_ObjectStorage();
		$this->objectType = str_replace(array('_Repository_', 'Repository'), array('_Model_', ''), $this->getRepositoryClassName());

		if ($objectManager === NULL) {
			// Legacy creation, in case the object manager is NOT injected
			// If ObjectManager IS there, then all properties are automatically injected
			$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
			$this->injectIdentityMap($this->objectManager->get('Tx_Extbase_Persistence_IdentityMap'));
			$this->injectQueryFactory($this->objectManager->get('Tx_Extbase_Persistence_QueryFactory'));
			$this->injectPersistenceManager($this->objectManager->get('Tx_Extbase_Persistence_Manager'));
		} else {
			$this->objectManager = $objectManager;
		}
	}

	/**
	 * @param Tx_Extbase_Persistence_IdentityMap $identityMap
	 * @return void
	 */
	public function injectIdentityMap(Tx_Extbase_Persistence_IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * @param Tx_Extbase_Persistence_QueryFactory $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(Tx_Extbase_Persistence_QueryFactory $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * @param Tx_Extbase_Persistence_Manager $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(Tx_Extbase_Persistence_Manager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
		$this->persistenceManager->registerRepositoryClassName($this->getRepositoryClassName());
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		if (!($object instanceof $this->objectType)) {
			throw new Tx_Extbase_Persistence_Exception_IllegalObjectType('The object given to add() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
		}

		$this->addedObjects->attach($object);
		$this->removedObjects->detach($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if (!($object instanceof $this->objectType)) {
			throw new Tx_Extbase_Persistence_Exception_IllegalObjectType('The object given to remove() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
		}

		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @return void
	 * @api
	 */
	public function replace($existingObject, $newObject) {
		if (!($existingObject instanceof $this->objectType)) {
			throw new Tx_Extbase_Persistence_Exception_IllegalObjectType('The existing object given to replace was not of the type (' . $this->objectType . ') this repository manages.', 1248363434);
		}
		if (!($newObject instanceof $this->objectType)) {
			throw new Tx_Extbase_Persistence_Exception_IllegalObjectType('The new object given to replace was not of the type (' . $this->objectType . ') this repository manages.', 1248363439);
		}

		$backend = $this->persistenceManager->getBackend();
		$session = $this->persistenceManager->getSession();
		$uuid = $backend->getIdentifierByObject($existingObject);
		if ($uuid !== NULL) {
			$backend->replaceObject($existingObject, $newObject);
			$session->unregisterReconstitutedObject($existingObject);
			$session->registerReconstitutedObject($newObject);

			if ($this->removedObjects->contains($existingObject)) {
				$this->removedObjects->detach($existingObject);
				$this->removedObjects->attach($newObject);
			}
		} elseif ($this->addedObjects->contains($existingObject)) {
			$this->addedObjects->detach($existingObject);
			$this->addedObjects->attach($newObject);
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnknownObject('The "existing object" is unknown to the persistence backend.', 1238068475);
		}

	}

	/**
	 * Replaces an existing object with the same identifier by the given object
	 *
	 * @param object $modifiedObject The modified object
	 * @api
	 */
	public function update($modifiedObject) {
		if (!($modifiedObject instanceof $this->objectType)) {
			throw new Tx_Extbase_Persistence_Exception_IllegalObjectType('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}

		$uid = $modifiedObject->getUid();
		if ($uid !== NULL) {
			$existingObject = $this->findByUid($uid);
			$this->replace($existingObject, $modifiedObject);
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnknownObject('The "modified object" is does not have an existing counterpart in this repository.', 1249479819);
		}
	}

	/**
	 * Returns all addedObjects that have been added to this repository with add().
	 *
	 * This is a service method for the persistence manager to get all addedObjects
	 * added to the repository. Those are only objects *added*, not objects
	 * fetched from the underlying storage.
	 *
	 * @return Tx_Extbase_Persistence_ObjectStorage the objects
	 */
	public function getAddedObjects() {
		return $this->addedObjects;
	}

	/**
	 * Returns an Tx_Extbase_Persistence_ObjectStorage with objects remove()d from the repository
	 * that had been persisted to the storage layer before.
	 *
	 * @return Tx_Extbase_Persistence_ObjectStorage the objects
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 * @api
	 */
	public function findAll() {
		$result = $this->createQuery()->execute();
		return $result;
	}

	/**
	 * Returns the total number objects of this repository.
	 *
	 * @return integer The object count
	 * @api
	 */
	public function countAll() {
		return $this->createQuery()->execute()->count();
	}

	/**
	 * Removes all objects of this repository as if remove() was called for
	 * all of them.
	 *
	 * @return void
	 * @api
	 */
	public function removeAll() {
		$this->addedObjects = new Tx_Extbase_Persistence_ObjectStorage();
		foreach ($this->findAll() as $object) {
			$this->remove($object);
		}
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param int $uid The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 */
	public function findByUid($uid) {
		if ($this->identityMap->hasIdentifier($uid, $this->objectType)) {
			$object = $this->identityMap->getObjectByIdentifier($uid, $this->objectType);
		} else {
			$query = $this->createQuery();
			$query->getQuerySettings()->setRespectSysLanguage(FALSE);
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			$object = $query->matching($query->equals('uid', $uid))
					->execute()
					->getFirst();
			if ($object !== NULL) {
				$this->identityMap->registerObject($object, $uid);
			}
		}
		return $object;
	}

	/**
	 * Sets the property names to order the result by per default.
	 * Expected like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $defaultOrderings The property names to order by
	 * @return void
	 * @api
	 */
	public function setDefaultOrderings(array $defaultOrderings) {
		$this->defaultOrderings = $defaultOrderings;
	}

	/**
	 * Sets the default query settings to be used in this repository
	 *
	 * @param Tx_Extbase_Persistence_QuerySettingsInterface $defaultQuerySettings The query settings to be used by default
	 * @return void
	 * @api
	 */
	public function setDefaultQuerySettings(Tx_Extbase_Persistence_QuerySettingsInterface $defaultQuerySettings) {
		$this->defaultQuerySettings = $defaultQuerySettings;
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function createQuery() {
		$query = $this->queryFactory->create($this->objectType);
		if ($this->defaultOrderings !== array()) {
			$query->setOrderings($this->defaultOrderings);
		}
		if ($this->defaultQuerySettings !== NULL) {
			$query->setQuerySettings($this->defaultQuerySettings);
		}
		return $query;
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
			return $result;
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = strtolower(substr(substr($methodName, 9), 0, 1) ) . substr(substr($methodName, 9), 1);
			$query = $this->createQuery();
			$object = $query->matching($query->equals($propertyName, $arguments[0]))
				->setLimit(1)
				->execute()
				->getFirst();
			return $object;
		} elseif (substr($methodName, 0, 7) === 'countBy' && strlen($methodName) > 8) {
			$propertyName = strtolower(substr(substr($methodName, 7), 0, 1) ) . substr(substr($methodName, 7), 1);
			$query = $this->createQuery();
			$result = $query->matching($query->equals($propertyName, $arguments[0]))
				->execute()
				->count();
			return $result;
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