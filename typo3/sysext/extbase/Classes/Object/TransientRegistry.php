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
 * A transient Object Object Cache which provides a transient memory-based
 * registry of objects.
 *
 * @package Extbase
 * @subpackage Object
 * @version $Id: TransientRegistry.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Object_TransientRegistry implements Tx_Extbase_Object_RegistryInterface {

	/**
	 * @var array Location where objects are stored
	 */
	protected $objects = array();

	/**
	 * Returns an object from the registry. If an instance of the required
	 * object does not exist yet, an exception is thrown.
	 *
	 * @param string $objectName Name of the object to return an object of
	 * @return object The object
	 */
	public function getObject($objectName) {
		if (!$this->objectExists($objectName)) throw new RuntimeException('Object "' . $objectName . '" does not exist in the object registry.', 1167917198);
		return $this->objects[$objectName];
	}

	/**
	 * Put an object into the registry.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object to store in the registry
	 * @return void
	 */
	public function putObject($objectName, $object) {
		if (!is_string($objectName) || strlen($objectName) === 0) throw new RuntimeException('No valid object name specified.', 1167919564);
		if (!is_object($object)) throw new RuntimeException('$object must be of type Object', 1167917199);
		$this->objects[$objectName] = $object;
	}

	/**
	 * Remove an object from the registry.
	 *
	 * @param string objectName Name of the object to remove the object for
	 * @return void
	 */
	public function removeObject($objectName) {
		if (!$this->objectExists($objectName)) throw new RuntimeException('Object "' . $objectName . '" does not exist in the object registry.', 1167917200);
		unset ($this->objects[$objectName]);
	}

	/**
	 * Checks if an object of the given object already exists in the object registry.
	 *
	 * @param string $objectName Name of the object to check for an object
	 * @return boolean TRUE if an object exists, otherwise FALSE
	 */
	public function objectExists($objectName) {
		return isset($this->objects[$objectName]);
	}

}

?>