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
 * Implementation of the default Extbase Object Manager
 *
 * @package Extbase
 * @subpackage Object
 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0
 * @see Tx_Extbase_Object_ObjectManagerInterface, Tx_Extbase_Object_ObjectManager
 */
class Tx_Extbase_Object_Manager extends Tx_Extbase_Object_ObjectManager {

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 *
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Factory's create() method and Singleton objects should rather be
	 * injected by some type of Dependency Injection.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0. Please use Tx_Extbase_Object_ObjectManager instead
	 */
	public function getObject($objectName) {
		t3lib_div::logDeprecatedFunction();
		$arguments = array_slice(func_get_args(), 1);
		if (in_array('t3lib_Singleton', class_implements($objectName))) {
			$object = $this->get($objectName, $arguments);
		} else {
			$object = $this->create($objectName, $arguments);
		}
		return $object;
	}

}

?>