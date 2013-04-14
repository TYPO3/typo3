<?php
namespace TYPO3\CMS\Extbase\Error;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
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
 * Result object for operations dealing with objects, such as the Property Mapper or the Validators.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Result {

	/**
	 * @var array<\TYPO3\CMS\Extbase\Error\Error>
	 */
	protected $errors = array();

	/**
	 * @var array<\TYPO3\CMS\Extbase\Error\Warning>
	 */
	protected $warnings = array();

	/**
	 * @var array<\TYPO3\CMS\Extbase\Error\Notice>
	 */
	protected $notices = array();

	/**
	 * The result objects for the sub properties
	 *
	 * @var array<\TYPO3\CMS\Extbase\Error\Result>
	 */
	protected $propertyResults = array();

	/**
	 * Add an error to the current Result object
	 *
	 * @param \TYPO3\CMS\Extbase\Error\Error $error
	 * @return void
	 * @api
	 */
	public function addError(\TYPO3\CMS\Extbase\Error\Error $error) {
		$this->errors[] = $error;
	}

	/**
	 * Add a warning to the current Result object
	 *
	 * @param \TYPO3\CMS\Extbase\Error\Warning $warning
	 * @return void
	 * @api
	 */
	public function addWarning(\TYPO3\CMS\Extbase\Error\Warning $warning) {
		$this->warnings[] = $warning;
	}

	/**
	 * Add a notice to the current Result object
	 *
	 * @param \TYPO3\CMS\Extbase\Error\Notice $notice
	 * @return void
	 * @api
	 */
	public function addNotice(\TYPO3\CMS\Extbase\Error\Notice $notice) {
		$this->notices[] = $notice;
	}

	/**
	 * Get all errors in the current Result object (non-recursive)
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Error>
	 * @api
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Get all warnings in the current Result object (non-recursive)
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Warning>
	 * @api
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Get all notices in the current Result object (non-recursive)
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Notice>
	 * @api
	 */
	public function getNotices() {
		return $this->notices;
	}

	/**
	 * Get the first error object of the current Result object (non-recursive)
	 *
	 * @return \TYPO3\CMS\Extbase\Error\Error
	 * @api
	 */
	public function getFirstError() {
		reset($this->errors);
		return current($this->errors);
	}

	/**
	 * Get the first warning object of the current Result object (non-recursive)
	 *
	 * @return \TYPO3\CMS\Extbase\Error\Warning
	 * @api
	 */
	public function getFirstWarning() {
		reset($this->warnings);
		return current($this->warnings);
	}

	/**
	 * Get the first notice object of the curren Result object (non-recursive)
	 *
	 * @return \TYPO3\CMS\Extbase\Error\Notice
	 * @api
	 */
	public function getFirstNotice() {
		reset($this->notices);
		return current($this->notices);
	}

	/**
	 * Return a Result object for the given property path. This is
	 * a fluent interface, so you will proboably use it like:
	 * $result->forProperty('foo.bar')->getErrors() -- to get all errors
	 * for property "foo.bar"
	 *
	 * @param string $propertyPath
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 * @api
	 */
	public function forProperty($propertyPath) {
		if ($propertyPath === '' || $propertyPath === NULL) {
			return $this;
		}
		$propertyPathSegments = explode('.', $propertyPath);
		return $this->recurseThroughResult($propertyPathSegments);
	}

	/**
	 * Internal use only!
	 *
	 * @param array $pathSegments
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 */
	public function recurseThroughResult(array $pathSegments) {
		if (count($pathSegments) === 0) {
			return $this;
		}
		$propertyName = array_shift($pathSegments);
		if (!isset($this->propertyResults[$propertyName])) {
			$this->propertyResults[$propertyName] = new \TYPO3\CMS\Extbase\Error\Result();
		}
		return $this->propertyResults[$propertyName]->recurseThroughResult($pathSegments);
	}

	/**
	 * Internal use only!
	 *
	 * @param string $propertyName
	 * @param string $checkerMethodName
	 * @return boolean
	 */
	protected function hasProperty($propertyName, $checkerMethodName) {
		if (count($this->{$propertyName}) > 0) {
			return TRUE;
		}
		foreach ($this->propertyResults as $subResult) {
			if ($subResult->{$checkerMethodName}()) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Does the current Result object have Errors? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasErrors() {
		return $this->hasProperty('errors', 'hasErrors');
	}

	/**
	 * Does the current Result object have Warnings? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasWarnings() {
		return $this->hasProperty('warnings', 'hasWarnings');
	}

	/**
	 * Does the current Result object have Notices? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasNotices() {
		return $this->hasProperty('notices', 'hasNotices');
	}

	/**
	 * Get a list of all Error objects recursively. The result is an array,
	 * where the key is the property path where the error occured, and the
	 * value is a list of all errors (stored as array)
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Error>
	 * @api
	 */
	public function getFlattenedErrors() {
		$result = array();
		$this->flattenTree('errors', $result, array());
		return $result;
	}

	/**
	 * Get a list of all Warning objects recursively. The result is an array,
	 * where the key is the property path where the warning occured, and the
	 * value is a list of all warnings (stored as array)
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Warning>
	 * @api
	 */
	public function getFlattenedWarnings() {
		$result = array();
		$this->flattenTree('warnings', $result, array());
		return $result;
	}

	/**
	 * Get a list of all Notice objects recursively. The result is an array,
	 * where the key is the property path where the notice occured, and the
	 * value is a list of all notices (stored as array)
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Notice>
	 * @api
	 */
	public function getFlattenedNotices() {
		$result = array();
		$this->flattenTree('notices', $result, array());
		return $result;
	}

	/**
	 * Only use internally!
	 *
	 * Flatten a tree of Result objects, based on a certain property.
	 *
	 * @param string $propertyName
	 * @param array &$result
	 * @param array $level
	 * @return void
	 */
	public function flattenTree($propertyName, &$result, $level) {
		if (count($this->{$propertyName}) > 0) {
			$result[implode('.', $level)] = $this->{$propertyName};
		}
		foreach ($this->propertyResults as $subPropertyName => $subResult) {
			array_push($level, $subPropertyName);
			$subResult->flattenTree($propertyName, $result, $level);
			array_pop($level);
		}
	}

	/**
	 * Merge the given Result object into this one.
	 *
	 * @param \TYPO3\CMS\Extbase\Error\Result $otherResult
	 * @return void
	 * @api
	 */
	public function merge(\TYPO3\CMS\Extbase\Error\Result $otherResult) {
		$this->mergeProperty($otherResult, 'getErrors', 'addError');
		$this->mergeProperty($otherResult, 'getWarnings', 'addWarning');
		$this->mergeProperty($otherResult, 'getNotices', 'addNotice');
		foreach ($otherResult->getSubResults() as $subPropertyName => $subResult) {
			$this->forProperty($subPropertyName)->merge($subResult);
		}
	}

	/**
	 * Merge a single property from the other result object.
	 *
	 * @param \TYPO3\CMS\Extbase\Error\Result $otherResult
	 * @param string $getterName
	 * @param string $adderName
	 * @return void
	 */
	protected function mergeProperty(\TYPO3\CMS\Extbase\Error\Result $otherResult, $getterName, $adderName) {
		foreach ($otherResult->{$getterName}() as $messageInOtherResult) {
			$this->{$adderName}($messageInOtherResult);
		}
	}

	/**
	 * Get a list of all sub Result objects available.
	 *
	 * @return array<\TYPO3\CMS\Extbase\Error\Result>
	 */
	public function getSubResults() {
		return $this->propertyResults;
	}
}

?>