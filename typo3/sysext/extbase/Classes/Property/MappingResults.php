<?php
namespace TYPO3\CMS\Extbase\Property;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
class MappingResults {

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
	 * @param \TYPO3\CMS\Extbase\Error\Error $error The occured error
	 * @param string $propertyName The name of the property which caused the error
	 */
	public function addError(\TYPO3\CMS\Extbase\Error\Error $error, $propertyName) {
		$this->errors[$propertyName] = $error;
	}

	/**
	 * Returns all errors that occured so far
	 *
	 * @return array Array of \TYPO3\CMS\Extbase\Error\Error
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
		return count($this->errors) > 0;
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
		return count($this->warnings) > 0;
	}
}

?>