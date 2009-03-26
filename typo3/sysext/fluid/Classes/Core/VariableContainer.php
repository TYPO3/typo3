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
 * @version $Id: VariableContainer.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * VariableContainer which stores template variables.
 * Is used in two contexts:
 *
 * 1) Holds the current variables in the template
 * 2) Holds variables being set during Parsing (set in view helpers implementing the PostParse facet)
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: VariableContainer.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_Core_VariableContainer {

	/**
	 * Objects stored in context
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Object factory
	 * @var Tx_Fluid_Object_FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Constructor. Can take an array, and initializes the objects with it.
	 *
	 * @param array $objectArray
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($objectArray = array()) {
		if (!is_array($objectArray)) throw new Tx_Fluid_Core_RuntimeException('Context has to be initialized with an array, ' . gettype($objectArray) . ' given.', 1224592343);
		$this->objects = $objectArray;
	}

	/**
	 * Inject object factory. Needed to instanciate view helpers in the syntax tree.
	 *
	 * @param Tx_Fluid_Compatibility_ObjectFactory Object Factory
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectObjectFactory(Tx_Fluid_Compatibility_ObjectFactory $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Get object factory. Needed to instanciate view helpers in the syntax tree. For internal use only!
	 *
	 * @return Tx_Fluid_Compatibility_ObjectFactory Object Factory
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function getObjectFactory() {
		return $this->objectFactory;
	}

	/**
	 * Add an object to the context
	 *
	 * @param string $identifier
	 * @param object $object
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function add($identifier, $object) {
		if (array_key_exists($identifier, $this->objects)) throw new Tx_Fluid_Core_RuntimeException('Duplicate variable declarations!', 1224479063);
		$this->objects[$identifier] = $object;
	}

	/**
	 * Get an object from the context. Throws exception if object is not found in context.
	 *
	 * @param string $identifier
	 * @return object The object identified by $identifier
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function get($identifier) {
		if (!array_key_exists($identifier, $this->objects)) throw new Tx_Fluid_Core_RuntimeException('Tried to get a variable "' . $identifier . '" which is not stored in the context!', 1224479370);
		return $this->objects[$identifier];
	}

	/**
	 * Remove an object from context. Throws exception if object is not found in context.
	 *
	 * @param string $identifier The identifier to remove
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function remove($identifier) {
		if (!array_key_exists($identifier, $this->objects)) throw new Tx_Fluid_Core_RuntimeException('Tried to remove a variable "' . $identifier . '" which is not stored in the context!', 1224479372);
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
