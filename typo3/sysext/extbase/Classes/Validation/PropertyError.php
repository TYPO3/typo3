<?php
namespace TYPO3\CMS\Extbase\Validation;

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
 * This object holds validation errors for one property.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
class PropertyError extends \TYPO3\CMS\Extbase\Validation\Error {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Validation errors for property "%s"';

	/**
	 * @var string The error code
	 */
	protected $code = 1242859509;

	/**
	 * @var string The property name
	 */
	protected $propertyName;

	/**
	 * @var array An array of \TYPO3\CMS\Extbase\Validation\Error for the property
	 */
	protected $errors = array();

	/**
	 * Create a new property error with the given property name
	 *
	 * @param string $propertyName The property name
	 */
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		$this->message = sprintf($this->message, $propertyName);
	}

	/**
	 * Add errors
	 *
	 * @param array $errors Array of \TYPO3\CMS\Extbase\Validation\Error for the property
	 * @return void
	 */
	public function addErrors($errors) {
		$this->errors = array_merge($this->errors, $errors);
	}

	/**
	 * Get all errors for the property
	 *
	 * @return array An array of \TYPO3\CMS\Extbase\Validation\Error objects or an empty array if no errors occurred for the property
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Get the property name
	 *
	 * @return string The property name for this error
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}
}
