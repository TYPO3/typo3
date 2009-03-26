<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage Core
 * @version $Id: ViewHelperArguments.php 2024 2009-03-23 16:15:18Z bwaidelich $
 */

/**
 * Arguments list. Wraps an array, but only allows read-only methods on it.
 * Is available inside every view helper as $this->arguments - and you use it as if it was an array.
 * However, you can only read, and not write to it.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ViewHelperArguments.php 2024 2009-03-23 16:15:18Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_Core_ViewHelperArguments implements ArrayAccess {

	/**
	 * Array storing the arguments themselves
	 */
	protected $arguments = array();

	/**
	 * Constructor.
	 *
	 * @param array $arguments Array of arguments
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Checks if a given key exists in the array
	 *
	 * @param string $key Key to check
	 * @return boolean true if exists
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetExists($key) {
		return array_key_exists($key, $this->arguments);
	}

	/**
	 * Returns the value to the given key.
	 *
	 * @param  $key Key to get.
	 * @return object associated value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo change this (what?)
	 */
	public function offsetGet($key) {
		if (!array_key_exists($key, $this->arguments)) {
			return NULL;
		}

		return $this->arguments[$key];
	}

	/**
	 * Throw exception if you try to set a value.
	 *
	 * @param string $name
	 * @param object $value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetSet($name, $value) {
		throw new Tx_Fluid_Core_RuntimeException('Tried to set argument "' . $name . '", but setting arguments is forbidden.', 1236080693);
	}

	/**
	 * Throw exception if you try to unset a value.
	 *
	 * @param string $name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetUnset($name) {
		throw new Tx_Fluid_Core_RuntimeException('Tried to unset argument "' . $name . '", but setting arguments is forbidden.', 1236080702);
	}
}
?>