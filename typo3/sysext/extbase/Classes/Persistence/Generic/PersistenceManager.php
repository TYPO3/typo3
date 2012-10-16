<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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
	 * This is an array of registered repository class names.
	 *
	 * @var array
	 */
	protected $repositoryClassNames = array();

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
	 * Registers a repository
	 *
	 * @param string $className The class name of the repository to be reigistered
	 * @return void
	 */
	public function registerRepositoryClassName($className) {
		$this->repositoryClassNames[] = $className;
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
	 * @param boolean $useLazyLoading Set to TRUE if you want to use lazy loading for this object
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
		// fetch and inspect objects from all known repositories
		foreach ($this->repositoryClassNames as $repositoryClassName) {
			$repository = $this->objectManager->get($repositoryClassName);
			$aggregateRootObjects->addAll($repository->getAddedObjects());
			$removedObjects->addAll($repository->getRemovedObjects());
		}
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
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @api
	 */
	public function add($object) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @api
	 */
	public function remove($object) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 * @api
	 */
	public function update($object) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
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
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
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
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @api
	 */
	public function createQueryForType($type) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}
}
?>