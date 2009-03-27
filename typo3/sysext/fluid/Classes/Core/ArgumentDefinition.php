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
 * @version $Id: ArgumentDefinition.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * Argument definition - definition of each view helper
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ArgumentDefinition.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_Core_ArgumentDefinition {

	/**
	 * Name of argument
	 * @var string
	 */
	protected $name;

	/**
	 * Type of argument
	 * @var string
	 */
	protected $type;

	/**
	 * Description of argument
	 * @var string
	 */
	protected $description;

	/**
	 * Is argument required?
	 * @var boolean
	 */
	protected $required = FALSE;

	/**
	 * Default value for argument
	 * @var null
	 */
	protected $defaultValue = NULL;

	/**
	 * Constructor for this argument definition.
	 *
	 * @param string $name Name of argument
	 * @param string $type Type of argument
	 * @param string $description Description of argument
	 * @param boolean $required TRUE if argument is required
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($name, $type, $description, $required, $defaultValue = NULL) {
		$this->name = $name;
		$this->type = $type;
		$this->description = $description;
		$this->required = $required;
		$this->defaultValue = $defaultValue;
	}

	/**
	 * Get the name of the argument
	 *
	 * @return string Name of argument
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the type of the argument
	 *
	 * @return string Type of argument
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Get the description of the argument
	 *
	 * @return string Description of argument
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Get the optionality of the argument
	 *
	 * @return boolean TRUE if argument is optional
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function isRequired() {
		return $this->required;
	}

	/**
	 * Get the default value, if set
	 *
	 * @return mixed Default value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}
}

?>