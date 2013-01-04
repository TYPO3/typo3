<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2012 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
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
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $session;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $defaultQuerySettings = NULL;

	/**
	 * Objects of this repository
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $addedObjects;

	/**
	 * Objects removed but not found in $this->addedObjects at removal time
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $removedObjects;

	public function initializeObject() {
		$this->addedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->removedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Returns all addedObjects that have been added to this repository with add().
	 *
	 * This is a service method for the persistence manager to get all addedObjects
	 * added to the repository. Those are only objects *added*, not objects
	 * fetched from the underlying storage.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage the objects
	 */
	public function getAddedObjects() {
		return $this->addedObjects;
	}

	/**
	 * Returns an \TYPO3\CMS\Extbase\Persistence\ObjectStorage with objects remove()d from the repository
	 * that had been persisted to the storage layer before.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage the objects
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
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
	 * Injects the Persistence Session
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $session The persistence session
	 * @return void
	 */
	public function injectSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $session) {
		$this->session = $session;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
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
		return $this->backend->getIdentifierByObject($object);
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @param boolean $useLazyLoading
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {
		return $this->backend->getObjectByIdentifier($identifier, $objectType);
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		$aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$removedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();

		$aggregateRootObjects->addAll($this->getAddedObjects());
		$removedObjects->addAll($this->getRemovedObjects());

		foreach ($this->session->getReconstitutedObjects() as $reconstitutedObject) {
			$className = get_class($reconstitutedObject);
			$delimiter = strpos($className, '_') !== FALSE ? '_' : '\\';
			$possibleRepositoryClassName = str_replace($delimiter . 'Model' . $delimiter, $delimiter . 'Repository' . $delimiter, $className) . 'Repository';
			if (class_exists($possibleRepositoryClassName)) {
				$aggregateRootObjects->attach($reconstitutedObject);
			}
		}
		// hand in only aggregate roots, leaving handling of subobjects to
		// the underlying storage layer
		$this->backend->setAggregateRootObjects($aggregateRootObjects);
		$this->backend->setDeletedObjects($removedObjects);
		$this->backend->commit();
		// this needs to unregister more than just those, as at least some of
		// the subobjects are supposed to go away as well...
		// OTOH those do no harm, changes to the unused ones should not happen,
		// so all they do is eat some memory.
		foreach ($removedObjects as $removedObject) {
			$this->session->unregisterReconstitutedObject($removedObject);
		}
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
		if ($this->removedObjects->contains($object)) {
			$this->removedObjects->detach($object);
		}
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
		}

		if (!$object->_isNew()) {
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
		$uid = $object->getUid();
		if ($uid !== NULL) {
			$existingObject = $this->getObjectByIdentifier($uid, get_class($object));
			$this->replace($existingObject, $object);
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The "modified object" is does not have an existing counterpart in this repository.', 1249479819);
		}
	}

	/**
	 * Injects the Extbase settings, called by Extbase.
	 *
	 * @param array $settings
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @return void
	 * @api
	 */
	public function injectSettings(array $settings) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Initializes the persistence manager, called by Extbase.
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @return void
	 * @api
	 */
	public function initialize() {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
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
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @api
	 */
	public function isNewObject($object) {
		return $this->backend->isNewObject($object);
	}

	/**
	 * Converts the given object into an array containing the identity of the domain object.
	 *
	 * @param object $object The object to be converted
	 * @return array The identity array in the format array('__identity' => '...')
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException if the given object is not known to the Persistence Manager
	 * @api
	 */
	public function convertObjectToIdentityArray($object) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Recursively iterates through the given array and turns objects
	 * into arrays containing the identity of the domain object.
	 *
	 * @param array $array The array to be iterated over
	 * @return array The modified array without objects
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException if array contains objects that are not known to the Persistence Manager
	 * @api
	 * @see convertObjectToIdentityArray()
	 */
	public function convertObjectsToIdentityArrays(array $array) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function createQueryForType($type) {
		/** @var $query \TYPO3\CMS\Extbase\Persistence\Generic\Query */
		$query = $this->queryFactory->create($type);
		if ($this->defaultQuerySettings !== NULL) {
			$query->setQuerySettings(clone $this->defaultQuerySettings);
		}

		return $query;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings
	 */
	public function setDefaultQuerySettings($defaultQuerySettings) {
		$this->defaultQuerySettings = $defaultQuerySettings;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	public function getDefaultQuerySettings() {
		return $this->defaultQuerySettings;
	}

	/**
	 * @param $existingObject
	 * @param $newObject
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function replace($existingObject, $newObject) {
		if (get_class($existingObject) !== get_class($newObject)) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The existing object and the object given to replace were not of the same type.', 1350243147);
		}

		$uuid = $this->backend->getIdentifierByObject($existingObject);
		if ($uuid !== NULL) {
			$this->backend->replaceObject($existingObject, $newObject);
			$this->session->unregisterReconstitutedObject($existingObject);
			$this->session->registerReconstitutedObject($newObject);
			if ($this->removedObjects->contains($existingObject)) {
				$this->removedObjects->detach($existingObject);
				$this->removedObjects->attach($newObject);
			} elseif ($this->addedObjects->contains($existingObject)) {
				$this->addedObjects->detach($existingObject);
				$this->addedObjects->attach($newObject);
			}
		} elseif ($this->addedObjects->contains($existingObject)) {
			$this->addedObjects->detach($existingObject);
			$this->addedObjects->attach($newObject);
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The "existing object" is unknown to the persistence backend.', 1238068475);
		}
	}
}

?>