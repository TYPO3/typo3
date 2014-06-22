<?php
namespace TYPO3\CMS\Extbase\Property;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * The Mapping Results
 *
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
class MappingResults {

	/**
	 * @var array An array of the occurred errors
	 */
	protected $errors = array();

	/**
	 * @var array An array of the occurred warnings
	 */
	protected $warnings = array();

	/**
	 * Adds an error to the mapping results. This might be for example a
	 * validation or mapping error
	 *
	 * @param \TYPO3\CMS\Extbase\Error\Error $error The occurred error
	 * @param string $propertyName The name of the property which caused the error
	 */
	public function addError(\TYPO3\CMS\Extbase\Error\Error $error, $propertyName) {
		$this->errors[$propertyName] = $error;
	}

	/**
	 * Returns all errors that occurred so far
	 *
	 * @return array Array of \TYPO3\CMS\Extbase\Error\Error
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns true if any error was recognized
	 *
	 * @return boolean True if an error occurred
	 */
	public function hasErrors() {
		return count($this->errors) > 0;
	}

	/**
	 * Adds a warning to the mapping results. This might be for example a
	 * property that could not be mapped but wasn't marked as required.
	 *
	 * @param string $warning The occurred warning
	 * @param string $propertyName The name of the property which caused the error
	 */
	public function addWarning($warning, $propertyName) {
		$this->warnings[$propertyName] = $warning;
	}

	/**
	 * Returns all warnings that occurred so far
	 *
	 * @return array Array of warnings
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Returns TRUE if any warning was recognized
	 *
	 * @return boolean TRUE if a warning occurred
	 */
	public function hasWarnings() {
		return count($this->warnings) > 0;
	}
}
