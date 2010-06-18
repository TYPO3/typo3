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
 * @version $Id: TemplateVariableContainer.php 2043 2010-03-16 08:49:45Z sebastian $
 * @package Fluid
 * @subpackage Core\ViewHelper
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_Core_ViewHelper_TemplateVariableContainer implements ArrayAccess {

	/**
	 * List of reserved words that can't be used as variable identifiers in Fluid templates
	 * @var array
	 */
	static protected $reservedVariableNames = array('true', 'false', 'on', 'off', 'yes', 'no');

	/**
	 * Variables stored in context
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Constructor. Can take an array, and initializes the variables with it.
	 *
	 * @param array $variableArray
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function __construct(array $variableArray = array()) {
		$this->variables = $variableArray;
	}

	/**
	 * Add a variable to the context
	 *
	 * @param string $identifier Identifier of the variable to add
	 * @param mixed $value The variable's value
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function add($identifier, $value) {
		if (array_key_exists($identifier, $this->variables)) throw new Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException('Duplicate variable declarations!', 1224479063);
		if (in_array(strtolower($identifier), self::$reservedVariableNames)) throw new Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException('"' . $identifier . '" is a reserved variable name and can\'t be used as variable identifier.', 1256730379);
		$this->variables[$identifier] = $value;
	}

	/**
	 * Get a variable from the context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier
	 * @return variable The variable identified by $identifier
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function get($identifier) {
		if (!array_key_exists($identifier, $this->variables)) throw new Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException('Tried to get a variable "' . $identifier . '" which is not stored in the context!', 1224479370);
		return $this->variables[$identifier];
	}

	/**
	 * Remove a variable from context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier The identifier to remove
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function remove($identifier) {
		if (!array_key_exists($identifier, $this->variables)) throw new Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException('Tried to remove a variable "' . $identifier . '" which is not stored in the context!', 1224479372);
		unset($this->variables[$identifier]);
	}

	/**
	 * Returns an array of all identifiers available in the context.
	 *
	 * @return array Array of identifier strings
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAllIdentifiers() {
		return array_keys($this->variables);
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
		return array_key_exists($identifier, $this->variables);
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __sleep() {
		return array('variables');
	}

	/**
	 * Adds a variable to the context.
	 *
	 * @param string $identifier Identifier of the variable to add
	 * @param mixed $value The variable's value
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetSet($identifier, $value) {
		return $this->add($identifier, $value);
	}

	/**
	 * Remove a variable from context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier The identifier to remove
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetUnset($identifier) {
		return $this->remove($identifier);
	}

	/**
	 * Checks if this property exists in the VariableContainer.
	 *
	 * @param string $identifier
	 * @return boolean TRUE if $identifier exists, FALSE otherwise
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetExists($identifier) {
		return $this->exists($identifier);
	}

	/**
	 * Get a variable from the context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier
	 * @return variable The variable identified by $identifier
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function offsetGet($identifier) {
		return $this->get($identifier);
	}
}
?>