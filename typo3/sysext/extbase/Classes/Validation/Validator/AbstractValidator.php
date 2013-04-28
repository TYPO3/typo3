<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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
 * Abstract validator
 */
abstract class AbstractValidator implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1. You should use constructor parameter to set validation options.
	 */
	protected $errors = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Error\Result
	 */
	protected $result;

	/**
	 * Sets options for the validator
	 *
	 * @param array $validationOptions Options for the validator
	 * @api
	 */
	public function __construct($validationOptions = array()) {
		$this->options = $validationOptions;
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occured.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\CMS\Extbase\Error\Result();
		$this->isValid($value);
		return $this->result;
	}

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @param mixed $value
	 * @return void
	 */
	abstract protected function isValid($value);

	/**
	 * Sets options for the validator
	 *
	 * @param array $options Options for the validator
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1. use constructor instead.
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of \TYPO3\CMS\Extbase\Validation\Error objects or an empty array if no errors occurred.
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1. use validate() instead.
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Creates a new validation error object and adds it to $this->errors
	 *
	 * @param string $message The error message
	 * @param integer $code The error code (a unix timestamp)
	 * @param array $arguments Arguments to be replaced in message
	 * @param string $title title of the error
	 * @return void
	 */
	protected function addError($message, $code, array $arguments = array(), $title = '') {
		if ($this->result !== NULL) {
			// backwards compatibility before Extbase 1.4.0: we cannot expect the "result" object to be there.
			$this->result->addError(new \TYPO3\CMS\Extbase\Validation\Error($message, $code, $arguments, $title));
		}
		// the following is @deprecated since Extbase 1.4.0:
		$this->errors[] = new \TYPO3\CMS\Extbase\Validation\Error($message, $code, $arguments, $title);
	}
}

?>