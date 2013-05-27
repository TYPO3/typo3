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
 * VariableContainer which stores template variables.
 * Is used in two contexts:
 *
 * 1) Holds the current variables in the template
 * 2) Holds variables being set during Parsing (set in view helpers implementing the PostParse facet)
 *
 * @version $Id$
 * @package Fluid
 * @subpackage Core\ViewHelper
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_Core_ViewHelper_TemplateVariableContainer {

	/**
	 * List of reserved words that can't be used as object identifiers in Fluid templates
	 * @var array
	 */
	static protected $reservedKeywords = array('true', 'false');

	/**
	 * Objects stored in context
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Constructor. Can take an array, and initializes the objects with it.
	 *
	 * @param array $objectArray
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function __construct($objectArray = array()) {
		if (!is_array($objectArray)) throw new RuntimeException('Context has to be initialized with an array, ' . gettype($objectArray) . ' given.', 1224592343);
		$this->objects = $objectArray;
	}

	/**
	 * Add an object to the context
	 *
	 * @param string $identifier
	 * @param object $object
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function add($identifier, $object) {
		if (array_key_exists($identifier, $this->objects)) throw new RuntimeException('Duplicate variable declarations!', 1224479063);
		if (in_array(strtolower($identifier), self::$reservedKeywords)) throw new RuntimeException('"' . $identifier . '" is a reserved keyword and can\'t be used as variable identifier.', 1256730379);
		$this->objects[$identifier] = $object;
	}

	/**
	 * Get an object from the context. Throws exception if object is not found in context.
	 *
	 * @param string $identifier
	 * @return object The object identified by $identifier
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function get($identifier) {
		if (!array_key_exists($identifier, $this->objects)) throw new RuntimeException('Tried to get a variable "' . $identifier . '" which is not stored in the context!', 1224479370);
		return $this->objects[$identifier];
	}

	/**
	 * Remove an object from context. Throws exception if object is not found in context.
	 *
	 * @param string $identifier The identifier to remove
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function remove($identifier) {
		if (!array_key_exists($identifier, $this->objects)) throw new RuntimeException('Tried to remove a variable "' . $identifier . '" which is not stored in the context!', 1224479372);
		unset($this->objects[$identifier]);
	}

	/**
	 * Returns an array of all identifiers available in the context.
	 *
	 * @return array Array of identifier strings
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAllIdentifiers() {
		return array_keys($this->objects);
	}

	/**
	 * Checks if this property exists in the VariableContainer.
	 *
	 * @param string $identifier
	 * @return boolean TRUE if $identifier exists, FALSE otherwise
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function exists($identifier) {
		return array_key_exists($identifier, $this->objects);
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __sleep() {
		return array('objects');
	}
}
?>