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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_ObjectStorage.php');

/**
 * The persistence session - acts as a Unit of Work for EXCMVC's persistence framework.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @prototype
 */
class TX_EXTMVC_Persistence_Session {
		
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
	 * Constructs a new Session
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->addedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->removedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->reconstitutedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
	}

	/**
	 * Registers an added object
	 *
	 * @param object $object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function registerAddedObject($object) {
		$this->addedObjects->attach($object);
	}

	/**
	 * Unegisters an added object
	 *
	 * @param object $object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function unregisterAddedObject($object) {
		$this->addedObjects->detach($object);
	}
	
	/**
	 * Returns all objects which have been registered as added objects
	 *
	 * @return array All added objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getAddedObjects() {
		return $this->addedObjects;
	}

	/**
	 * Registers a removed object
	 *
	 * @param object $object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function registerRemovedObject($object) {
		$this->removedObjects->attach($object);
	}

	/**
	 * Unegisters a removed object
	 *
	 * @param object $object
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function unregisterRemovedObject($object) {
		$this->removedObjects->detach($object);
	}
	
	/**
	 * Returns all objects which have been registered as removed objects
	 *
	 * @return array All removed objects
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
	}

	/**
	 * Registers a reconstituted object
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerReconstitutedObject($object) {
		$this->reconstitutedObjects->attach($object);
	}

	/**
	 * Unregisters a reconstituted object
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterReconstitutedObject($object) {
		$this->reconstitutedObjects->detach($object);
	}

	/**
	 * Returns all objects which have been registered as reconstituted objects
	 *
	 * @return array All reconstituted objects
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getReconstitutedObjects() {
		return $this->reconstitutedObjects;
	}

}
?>