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
 * The Extbase Persistence Manager
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id$
 * @api
 */
class Tx_Extbase_Persistence_Manager implements Tx_Extbase_Persistence_ManagerInterface, t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_BackendInterface
	 */
	protected $backend;

	/**
	 * @var Tx_Extbase_Persistence_Session
	 */
	protected $session;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
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
	 * @param Tx_Extbase_Persistence_BackendInterface $backend The persistence backend
	 * @return void
	 */
	public function injectBackend(Tx_Extbase_Persistence_BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 *
	 * Injects the Persistence Session
	 *
	 * @param Tx_Extbase_Persistence_Session $session The persistence session
	 * @return void
	 */
	public function injectSession(Tx_Extbase_Persistence_Session $session) {
		$this->session = $session;
	}

	/**
	 * Injects the object manager
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return Tx_Extbase_Persistence_Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the persistence backend
	 *
	 * @return Tx_Extbase_Persistence_BackendInterface
	 */
	public function getBackend() {
		return $this->backend;
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
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
		return $this->backend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
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
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType) {
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
		$aggregateRootObjects = new Tx_Extbase_Persistence_ObjectStorage();
		$removedObjects = new Tx_Extbase_Persistence_ObjectStorage();

			// fetch and inspect objects from all known repositories
		foreach ($this->repositoryClassNames as $repositoryClassName) {
			$repository = $this->objectManager->get($repositoryClassName);
			$aggregateRootObjects->addAll($repository->getAddedObjects());
			$removedObjects->addAll($repository->getRemovedObjects());
		}

		foreach ($this->session->getReconstitutedObjects() as $reconstitutedObject) {
			if (class_exists(str_replace('_Model_', '_Repository_', get_class($reconstitutedObject)) . 'Repository')) {
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
		foreach($removedObjects as $removedObject) {
			$this->session->unregisterReconstitutedObject($removedObject);
		}
	}

}
?>