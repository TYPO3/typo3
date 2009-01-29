<?php
declare(ENCODING = 'utf-8');

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
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_AbstractDomainObject.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_ObjectStorage.php');

/**
 * The persistence session - acts as a Unit of Work for EXCMVC's persistence framework.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_Session implements t3lib_singleton {
// TODO Implement against SessionInterface
		
	/**
	 * Objects added to the repository but not yet persisted
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $addedObjects;

	/**
	 * Objects removed but not yet persisted
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $removedObjects;

	/**
	 * Reconstituted objects
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $reconstitutedObjects;

	/**
	 * Repositories
	 *
	 * @var array
	 */
	protected $repositoryClassNames = array();

	/**
	 * Constructs a new Session
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function __construct() {
		$this->addedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->removedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->reconstitutedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
	}

	/**
	 * Registers an added object
	 *
	 * @param TX_EXTMVC_AbstractDomainObject $object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function registerAddedObject(TX_EXTMVC_AbstractDomainObject $object) {
		$this->removedObjects->detach($object);
		$this->addedObjects->attach($object);
	}

	/**
	 * Returns all objects which have been registered as added objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return TX_EXTMVC_Persistence_ObjectStorage All added objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getAddedObjects($objectClassName = NULL) {
		$addedObjects = array();
		foreach ($this->addedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			$addedObjects[] = $object;
		}
		return $addedObjects;
	}

	/**
	 * Registers a removed object
	 *
	 * @param TX_EXTMVC_AbstractDomainObject $object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function registerRemovedObject(TX_EXTMVC_AbstractDomainObject $object) {
		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Returns all objects which have been registered as removed objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return TX_EXTMVC_Persistence_ObjectStorage All removed objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getRemovedObjects($objectClassName = NULL) {
		$removedObjects = array();
		foreach ($this->removedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			$removedObjects[] = $object;
		}
		return $removedObjects;
	}

	/**
	 * Registers a reconstituted object
	 *
	 * @param object $object
	 * @return TX_EXTMVC_AbstractDomainObject
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function registerReconstitutedObject(TX_EXTMVC_AbstractDomainObject $object) {
		$this->reconstitutedObjects->attach($object);
		$object->_memorizeCleanState();
	}

	/**
	 * Returns all objects which have been registered as reconstituted objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return TX_EXTMVC_Persistence_ObjectStorage All reconstituted objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getReconstitutedObjects($objectClassName = NULL) {
		$reconstitutedObjects = array();
		foreach ($this->reconstitutedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			$reconstitutedObjects[] = $object;
		}
		return $reconstitutedObjects;
	}
	
	/**
	 * Returns all objects marked as dirty (changed after reconstitution)
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return array An array of dirty objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getDirtyObjects($objectClassName = NULL) {
		$dirtyObjects = array();
		foreach ($this->reconstitutedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			if ($object->_isDirty()) {
				$dirtyObjects[] = $object;
			}
		}
		return $dirtyObjects;
	}	
	
	/**
	 * Clears all ObjectStorages
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function clear() {
		$this->addedObjects->removeAll();
		$this->removedObjects->removeAll();
		$this->reconstitutedObjects->removeAll();
		$this->repositoryClassNames = array();
	}
	
	/**
	 * Registers a repository to be managed by the session
	 *
	 * @param string $repositoryClassName The repository to be registered
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function registerRepository($repositoryClassName) {
		$this->repositoryClassNames[] = $repositoryClassName;
	}
	
	/**
	 * Unegisters a repository to be managed by the session
	 *
	 * @param string $repository The repository to be unregistered
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function unregisterRepository($repositoryClassName) {
		// TODO Implement unregisterRepository()
	}
	
	/**
	 * Returns all repository class names
	 *
	 * @return array An array holding the class names
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getRepositoryClassNames() {
		return $this->repositoryClassNames;
	}
	
	public function commit() {
		foreach ($this->repositoryClassNames as $repositoryClassName) {
			$repository = t3lib_div::makeInstance($repositoryClassName);
			$repository->persistAll();
		}
	}
	
}
?>