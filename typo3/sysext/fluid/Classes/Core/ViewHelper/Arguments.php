<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
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

/**
 * Arguments list. Wraps an array, but only allows read-only methods on it.
 * Is available inside every view helper as $this->arguments - and you use it as if it was an array.
 * However, you can only read, and not write to it.
 *
 * @version $Id: Arguments.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage Core\ViewHelper
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_Core_ViewHelper_Arguments implements ArrayAccess {

	/**
	 * Array storing the arguments themselves
	 */
	protected $arguments = array();

	/**
	 * Constructor.
	 *
	 * @param array $arguments Array of arguments
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function __construct(array $arguments) {
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
	 * @param string $key Key to get.
	 * @return object associated value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @param string $key
	 * @param object $value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetSet($key, $value) {
		throw new Tx_Fluid_Core_Exception('Tried to set argument "' . $key . '", but setting arguments is forbidden.', 1236080693);
	}

	/**
	 * Throw exception if you try to unset a value.
	 *
	 * @param string $key
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetUnset($key) {
		throw new Tx_Fluid_Core_Exception('Tried to unset argument "' . $key . '", but setting arguments is forbidden.', 1236080702);
	}

	/**
	 * Checks if an argument with the specified name exists
	 *
	 * @param string $argumentName Name of the argument to check for
	 * @return boolean TRUE if such an argument exists, otherwise FALSE
	 * @see offsetExists()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasArgument($argumentName) {
		return $this->offsetExists($argumentName) && $this->arguments[$argumentName] !== NULL;
	}
}
?>