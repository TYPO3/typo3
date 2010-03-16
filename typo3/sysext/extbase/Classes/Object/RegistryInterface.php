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
 * Object Object Cache Interface
 *
 * @package Extbase
 * @subpackage Object
 * @version $Id: RegistryInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Object_RegistryInterface {

	/**
	 * Returns an object from the registry. If an instance of the required
	 * object does not exist yet, an exception is thrown.
	 *
	 * @param string $objectName Name of the object to return an object of
	 * @return object The object
	 */
	public function getObject($objectName);

	/**
	 * Put an object into the registry.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object to store in the registry
	 * @return void
	 */
	public function putObject($objectName, $object);

	/**
	 * Remove an object from the registry.
	 *
	 * @param string $objectName Name of the object to remove the object for
	 * @return void
	 */
	public function removeObject($objectName);

	/**
	 * Checks if an object of the given object already exists in the object registry.
	 *
	 * @param string $objectName Name of the object to check for an object
	 * @return boolean TRUE if an object exists, otherwise FALSE
	 */
	public function objectExists($objectName);
}

?>