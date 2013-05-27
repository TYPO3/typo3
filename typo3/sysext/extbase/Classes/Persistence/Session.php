<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The persistence session - acts as a Unit of Work for Extbase persistence framework.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Session implements t3lib_singleton {

	/**
	 * Objects which were reconstituted. The relevant objects are registered by
	 * the Tx_Extbase_Persistence_Mapper_DataMapper.
	 *
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $reconstitutedObjects;

	/**
	 * Constructs a new Session
	 *
	 */
	public function __construct() {
		$this->reconstitutedObjects = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Registers a reconstituted object
	 *
	 * @param object $object
	 * @return void
	 */
	public function registerReconstitutedObject(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$this->reconstitutedObjects->attach($object);
	}

	/**
	 * Unregisters a reconstituted object
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object
	 * @return void
	 */
	public function unregisterReconstitutedObject(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$this->reconstitutedObjects->detach($object);
	}

	/**
	 * Returns all objects which have been registered as reconstituted objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return array All reconstituted objects
	 */
	public function getReconstitutedObjects() {
		return $this->reconstitutedObjects;
	}

}
?>