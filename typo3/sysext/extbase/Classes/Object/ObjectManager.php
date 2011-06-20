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
 */
class Tx_Extbase_Object_ObjectManager implements Tx_Extbase_Object_ObjectManagerInterface {

	/**
	 * @var Tx_Extbase_Object_Container_Container
	 */
	protected $objectContainer;

	/**
	 * Constructs a new Object Manager
	 */
	public function __construct() {
		$this->objectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container'); // Singleton
	}

	/**
	 * Returns TRUE if an object with the given name is registered
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 */
	public function isRegistered($objectName) {
		return class_exists($objectName, TRUE);
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 *
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Manager's create() method and Singleton objects should rather be
	 * injected by some type of Dependency Injection.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @api
	 */
	public function get($objectName) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->objectContainer->getInstance($objectName, $arguments);
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This factory method can only create objects of the scope prototype.
	 * Singleton objects must be either injected by some type of Dependency Injection or
	 * if that is not possible, be retrieved by the get() method of the
	 * Object Manager
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws Tx_Extbase_Object_Exception_WrongScropeException if the created object is not of scope prototype
	 * @api
	 */
	public function create($objectName) {
		$arguments = func_get_args();
		array_shift($arguments);
		$instance = $this->objectContainer->getInstance($objectName, $arguments);

		if ($instance instanceof t3lib_Singleton) {
			throw new Tx_Extbase_Object_Exception_WrongScope('Object "' . $objectName . '" is of not of scope prototype, but only prototype is supported by create()', 1265203124);
		}

		return $instance;
	}
}
?>