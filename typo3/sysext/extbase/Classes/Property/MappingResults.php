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
 * The Mapping Results
 *
 * @package Extbase
 * @subpackage Property
 * @version $Id: MappingResults.php 1052 2009-08-05 21:51:32Z sebastian $
 * @scope prototype
 */
class Tx_Extbase_Property_MappingResults {

	/**
	 * @var array An array of the occured errors
	 */
	protected $errors = array();

	/**
	 * @var array An array of the occured warnings
	 */
	protected $warnings = array();

	/**
	 * Adds an error to the mapping results. This might be for example a
	 * validation or mapping error
	 *
	 * @param Tx_Extbase_Error_Error $error The occured error
	 * @param string $propertyName The name of the property which caused the error
	 */
	public function addError(Tx_Extbase_Error_Error $error, $propertyName) {
		$this->errors[$propertyName] = $error;
	}

	/**
	 * Returns all errors that occured so far
	 *
	 * @return array Array of Tx_Extbase_Error_Error
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns true if any error was recognized
	 *
	 * @return boolean True if an error occured
	 */
	public function hasErrors() {
		return (count($this->errors) > 0);
	}

	/**
	 * Adds a warning to the mapping results. This might be for example a
	 * property that could not be mapped but wasn't marked as required.
	 *
	 * @param string $warning The occured warning
	 * @param string $propertyName The name of the property which caused the error
	 */
	public function addWarning($warning, $propertyName) {
		$this->warnings[$propertyName] = $warning;
	}

	/**
	 * Returns all warnings that occured so far
	 *
	 * @return array Array of warnings
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Returns TRUE if any warning was recognized
	 *
	 * @return boolean TRUE if a warning occured
	 */
	public function hasWarnings() {
		return (count($this->warnings) > 0);
	}
}

?>