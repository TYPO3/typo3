<?php
namespace TYPO3\CMS\Extbase\Validation;

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
	 * @return array An array of \TYPO3\CMS\Extbase\Validation\Error objects or an empty array if no errors occured for the property
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

?>