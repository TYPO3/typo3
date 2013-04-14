<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * The Extbase Persistence Manager
 *
 * @api
 */
class PersistenceManager implements \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $newObjects = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $changedObjects;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $addedObjects;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Backend
	 */
	protected $backend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * Create new instance
	 */
	public function __construct() {
		$this->addedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->removedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->changedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Injects the Persistence Backend
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface $backend The persistence backend
	 * @return void
	 */
	public function injectBackend(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Injects the Persistence Session
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $session The persistence session
	 * @return void
	 */
	public function injectPersistenceSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $session) {
		$this->persistenceSession = $session;
	}

	/**
	 * Registers a repository
	 *
	 * @param string $className The class name of the repository to be reigistered
	 * @deprecated since 6.1, will be remove two versions later
	 * @return void
	 */
	public function registerRepositoryClassName($className) {
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		return $this->backend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		return $this->backend->getObjectDataByQuery($query);
	}

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return mixed The identifier for the object if it is known, or NULL
	 * @api
	 */
	public function getIdentifierByObject($object) {
		return $this->persistenceSession->getIdentifierByObject($object);
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @param boolean $useLazyLoading Set to TRUE if you want to use lazy loading for this object
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {
		if (isset($this->newObjects[$identifier])) {
			return $this->newObjects[$identifier];
		}
		if ($this->persistenceSession->hasIdentifier($identifier, $objectType)) {
			return $this->persistenceSession->getObjectByIdentifier($identifier, $objectType);
		} else {
			return $this->backend->getObjectByIdentifier($identifier, $objectType);
		}
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		// hand in only aggregate roots, leaving handling of subobjects to
		// the underlying storage layer
		// reconstituted entities must be fetched from the session and checked
		// for changes by the underlying backend as well!
		$this->backend->setAggregateRootObjects($this->addedObjects);
		$this->backend->setChangedEntities($this->changedObjects);
		$this->backend->setDeletedEntities($this->removedObjects);
		$this->backend->commit();

		$this->addedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->removedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->changedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function createQueryForType($type) {
		return $this->queryFactory->create($type);
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->addedObjects->attach($object);
		$this->removedObjects->detach($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 * @api
	 */
	public function update($object) {
		if ($this->isNewObject($object)) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" given to update must be persisted already, but is new.', 1249479819);
		}
		$this->changedObjects->attach($object);
	}

	/**
	 * Injects the Extbase settings, called by Extbase.
	 *
	 * @param array $settings
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @api
	 */
	public function injectSettings(array $settings) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Initializes the persistence manager, called by Extbase.
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->backend->setPersistenceManager($this);
	}

	/**
	 * Clears the in-memory state of the persistence.
	 *
	 * Managed instances become detached, any fetches will
	 * return data directly from the persistence "backend".
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @return void
	 */
	public function clearState() {
		$this->newObjects = array();
		$this->addedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->removedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->changedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->persistenceSession->destroy();
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the persistence session
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->persistenceSession->hasObject($object) === FALSE);
	}

	/**
	 * Registers an object which has been created or cloned during this request.
	 *
	 * A "new" object does not necessarily
	 * have to be known by any repository or be persisted in the end.
	 *
	 * Objects registered with this method must be known to the getObjectByIdentifier()
	 * method.
	 *
	 * @param object $object The new object to register
	 * @return void
	 */
	public function registerNewObject($object) {
		$identifier = $this->getIdentifierByObject($object);
		$this->newObjects[$identifier] = $object;
	}

	/**
	 * Converts the given object into an array containing the identity of the domain object.
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @param object $object The object to be converted
	 * @api
	 */
	public function convertObjectToIdentityArray($object) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Recursively iterates through the given array and turns objects
	 * into arrays containing the identity of the domain object.
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @param array $array The array to be iterated over
	 * @api
	 * @see convertObjectToIdentityArray()
	 */
	public function convertObjectsToIdentityArrays(array $array) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Tear down the persistence
	 *
	 * This method is called in functional tests to reset the storage between tests.
	 * The implementation is optional and depends on the underlying persistence backend.
	 *
	 * @return void
	 */
	public function tearDown() {
		if (method_exists($this->backend, 'tearDown')) {
			$this->backend->tearDown();
		}
	}

}

?>