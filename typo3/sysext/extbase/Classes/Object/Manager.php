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
 * @version $Id: Manager.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Object_Manager implements Tx_Extbase_Object_ManagerInterface, t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_RegistryInterface
	 */
	protected $singletonObjectsRegistry;

	/**
	 * Constructs a new Object Manager
	 */
	public function __construct() {
		$this->singletonObjectsRegistry = t3lib_div::makeInstance('Tx_Extbase_Object_TransientRegistry'); // singleton
	}

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
	 * // TODO This is not part of the official API! Explain why.
	 */
	public function getObject($objectName) {
		if (in_array('t3lib_Singleton', class_implements($objectName))) {
			if ($this->singletonObjectsRegistry->objectExists($objectName)) {
				$object = $this->singletonObjectsRegistry->getObject($objectName);
			} else {
				$arguments = array_slice(func_get_args(), 1);
				$object = $this->makeInstance($objectName, $arguments);
				$this->singletonObjectsRegistry->putObject($objectName, $object);
			}
		} else {
			$arguments = array_slice(func_get_args(), 1);
			$object = $this->makeInstance($objectName, $arguments);
		}
		return $object;
	}

	/**
	 * Speed optimized alternative to ReflectionClass::newInstanceArgs().
	 * Delegates the instanciation to the makeInstance method of t3lib_div.
	 *
	 * @param string $objectName Name of the object to instantiate
	 * @param array $arguments Arguments to pass to t3lib_div::makeInstance
	 * @return object The object
	 */
	protected function makeInstance($objectName, array $arguments) {
		switch (count($arguments)) {
			case 0: return t3lib_div::makeInstance($objectName);
			case 1: return t3lib_div::makeInstance($objectName, $arguments[0]);
			case 2: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1]);
			case 3: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2]);
			case 4: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
			case 5: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
			case 6: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
			case 7: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6]);
			case 8: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7]);
			case 9: return t3lib_div::makeInstance($objectName, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7], $arguments[8]);
		}
		throw new Tx_Extbase_Object_Exception_CannotBuildObject('Object "' . $objectName . '" has too many arguments.', 1166550023);
	}


}

?>