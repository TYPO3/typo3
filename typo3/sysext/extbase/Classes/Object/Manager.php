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
 * @version $Id$
 */
class Tx_Extbase_Object_Manager implements Tx_Extbase_Object_ManagerInterface, t3lib_Singleton {

	/**
	 * @var Tx_Container_Container
	 */
	protected $objectContainer;

	/**
	 * Constructs a new Object Manager
	 */
	public function __construct() {
		$this->objectContainer = Tx_Container_Container::getContainer();
	}

	/**
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @deprecated since 1.3.0, will be removed in 1.5.0
	 */
	public function getObject($objectName) {
		return $this->get($objectName);
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @api
	 */
	public function get($objectName) {
		return call_user_func_array(array($this->objectContainer, 'getInstance'), func_get_args());
	}

	/**
	 * Registers a classname that should be used to resolve a given interface.
	 *
	 * Per default the interface's name stripped of "Interface" will be used.
	 * @param string $className
	 * @param string $alternativeClassName
	 */
	static public function registerImplementation($className, $alternativeClassName) {
		return Tx_Container_Container::getContainer()->registerImplementation($className, $alternativeClassName);
	}
}

?>