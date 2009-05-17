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
 * An Idetity Map for Domain Objects
 *
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Persistence_IdentityMap {

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $objectMap;

	/**
	 * @var array
	 */
	protected $uidMap = array();

	/**
	 * Constructs a new Identity Map
	 */
	public function __construct() {
		$this->objectMap = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given uiduid is known to the identity map
	 *
	 * @param string $className
	 * @param string $uid
	 * @return boolean
	 */
	public function hasUid($className, $uid) {
		if (is_array($this->uidMap[$className])) {
			return array_key_exists($uid, $this->uidMap[$className]);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the object for the given uid
	 *
	 * @param string $className
	 * @param string $uid
	 * @return object
	 */
	public function getObjectByUid($className, $uid) {
		return $this->uidMap[$className][$uid];
	}

	/**
	 * Returns the node identifier for the given object
	 *
	 * @param object $object
	 * @return string
	 */
	public function getUidByObject($object) {
		return $this->objectMap[$object];
	}

	/**
	 * Register a node identifier for an object
	 *
	 * @param object $object
	 * @param string $uid
	 */
	public function registerObject($object, $uid) {
		$this->objectMap[$object] = $uid;
		$this->uidMap[get_class($object)][$uid] = $object;
	}

	/**
	 * Unregister an object
	 *
	 * @param string $object
	 * @return void
	 */
	public function unregisterObject($object) {
		unset($this->uidMap[get_class($object)][$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}

}

?>