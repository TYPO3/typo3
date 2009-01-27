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
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_ObjectStorage.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_RepositoryInterface.php');

/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_Repository implements TX_EXTMVC_Persistence_RepositoryInterface, t3lib_Singleton {

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
	protected $session;

	/**
	 * Constructs a new Repository
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->objects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		$this->session->registerRepository(get_class($this));
	}
	
	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function add($object) {
		$this->objects->attach($object);
		$this->session->registerAddedObject($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function remove($object) {
		$this->objects->detach($object);
		$this->session->registerRemovedObject($object);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function findAll() {
		// TODO Reimplement the findAll() method
	}
	
	/**
	 * Persists changes (added, removed or changed objects) to the database.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function persistAll() {
		if ($this->session->getRemovedObjects()->count() > 0) $this->deleteRemoved();
		if ($this->session->getAddedObjects()->count() > 0) $this->insertAdded();
		// if ($this->session->getDirtyObjects()->count() > 0) $this->updateDirty();
	}
	
	// TODO implement magic find functions for public properties

}
?>