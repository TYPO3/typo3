<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
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
 * @api
 */
class TemplateVariableContainer implements \ArrayAccess {

	/**
	 * List of reserved words that can't be used as variable identifiers in Fluid templates
	 *
	 * @var array
	 */
	static protected $reservedVariableNames = array('true', 'false', 'on', 'off', 'yes', 'no', '_all');

	/**
	 * Variables stored in context
	 *
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Constructor. Can take an array, and initializes the variables with it.
	 *
	 * @param array $variableArray
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
	 * @throws Exception\InvalidVariableException
	 * @api
	 */
	public function add($identifier, $value) {
		if (array_key_exists($identifier, $this->variables)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('Duplicate variable declaration, "' . $identifier . '" already set!', 1224479063);
		}
		if (in_array(strtolower($identifier), self::$reservedVariableNames)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('"' . $identifier . '" is a reserved variable name and cannot be used as variable identifier.', 1256730379);
		}
		$this->variables[$identifier] = $value;
	}

	/**
	 * Get a variable from the context. Throws exception if variable is not found in context.
	 *
	 * If "_all" is given as identifier, all variables are returned in an array.
	 *
	 * @param string $identifier
	 * @return mixed The variable value identified by $identifier
	 * @throws Exception\InvalidVariableException
	 * @api
	 */
	public function get($identifier) {
		if ($identifier === '_all') {
			return $this->variables;
		}
		if (!array_key_exists($identifier, $this->variables)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('Tried to get a variable "' . $identifier . '" which is not stored in the context!', 1224479370);
		}
		return $this->variables[$identifier];
	}

	/**
	 * Remove a variable from context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier The identifier to remove
	 * @return void
	 * @throws Exception\InvalidVariableException
	 * @api
	 */
	public function remove($identifier) {
		if (!array_key_exists($identifier, $this->variables)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('Tried to remove a variable "' . $identifier . '" which is not stored in the context!', 1224479372);
		}
		unset($this->variables[$identifier]);
	}

	/**
	 * Returns an array of all identifiers available in the context.
	 *
	 * @return array Array of identifier strings
	 */
	public function getAllIdentifiers() {
		return array_keys($this->variables);
	}

	/**
	 * Returns the variables array.
	 *
	 * @return array Identifiers and values of all variables
	 */
	public function getAll() {
		return $this->variables;
	}

	/**
	 * Checks if this property exists in the VariableContainer.
	 *
	 * @param string $identifier
	 * @return boolean TRUE if $identifier exists, FALSE otherwise
	 * @api
	 */
	public function exists($identifier) {
		if ($identifier === '_all') {
			return TRUE;
		}

		return array_key_exists($identifier, $this->variables);
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
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
	 */
	public function offsetSet($identifier, $value) {
		$this->add($identifier, $value);
	}

	/**
	 * Remove a variable from context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier The identifier to remove
	 * @return void
	 */
	public function offsetUnset($identifier) {
		$this->remove($identifier);
	}

	/**
	 * Checks if this property exists in the VariableContainer.
	 *
	 * @param string $identifier
	 * @return boolean TRUE if $identifier exists, FALSE otherwise
	 */
	public function offsetExists($identifier) {
		return $this->exists($identifier);
	}

	/**
	 * Get a variable from the context. Throws exception if variable is not found in context.
	 *
	 * @param string $identifier
	 * @return mixed The variable identified by $identifier
	 */
	public function offsetGet($identifier) {
		return $this->get($identifier);
	}
}

?>