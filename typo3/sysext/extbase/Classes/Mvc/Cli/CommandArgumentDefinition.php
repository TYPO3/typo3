<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * Represents a CommandArgumentDefinition
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CommandArgumentDefinition {

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var boolean
	 */
	protected $required = FALSE;

	/**
	 * @var string
	 */
	protected $description = '';

	/**
	 * Constructor
	 *
	 * @param string $name name of the command argument (= parameter name)
	 * @param boolean $required defines whether this argument is required or optional
	 * @param string $description description of the argument
	 */
	public function __construct($name, $required, $description) {
		$this->name = $name;
		$this->required = $required;
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the lowercased name with dashes as word separator
	 *
	 * @return string
	 */
	public function getDashedName() {
		$dashedName = ucfirst($this->name);
		$dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
		return '--' . strtolower(substr($dashedName, 0, -1));
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function isRequired() {
		return $this->required;
	}
}

?>