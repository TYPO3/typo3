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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

/**
 * Abstract validator
 */
abstract class AbstractValidator implements ValidatorInterface {

	/**
	 * Specifies whether this validator accepts empty values.
	 *
	 * If this is TRUE, the validators isValid() method is not called in case of an empty value
	 * Note: A value is considered empty if it is NULL or an empty string!
	 * By default all validators except for NotEmpty and the Composite Validators accept empty values
	 *
	 * @var boolean
	 */
	protected $acceptsEmptyValues = TRUE;

	/**
	 * This contains the supported options, their default values, types and descriptions.
	 *
	 * @var array
	 */
	protected $supportedOptions = array();

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
	 * Constructs the validator and sets validation options
	 *
	 * @param array $options Options for the validator
	 * @throws InvalidValidationOptionsException
	 * @api
	 */
	public function __construct(array $options = array()) {
		// check for options given but not supported
		if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== array()) {
			throw new InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1379981890);
		}

		// check for required options being set
		array_walk(
			$this->supportedOptions,
			function($supportedOptionData, $supportedOptionName, $options) {
				if (isset($supportedOptionData[3]) && $supportedOptionData[3] === TRUE && !array_key_exists($supportedOptionName, $options)) {
					throw new InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1379981891);
				}
			},
			$options
		);

		// merge with default values
		$this->options = array_merge(
			array_map(
				function ($value) {
					return $value[0];
				},
				$this->supportedOptions
			),
			$options
		);
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\CMS\Extbase\Error\Result();
		if ($this->acceptsEmptyValues === FALSE || $this->isEmpty($value) === FALSE) {
			$this->isValid($value);
		}
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

	/**
	 * Returns the options of this validator
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param mixed $value
	 * @return boolean TRUE if the given $value is NULL or an empty string ('')
	 */
	final protected function isEmpty($value) {
		return $value === NULL || $value === '';
	}

	/**
	 * Wrap static call to LocalizationUtility to simplify unit testing
	 *
	 * @param string $translateKey
	 * @param string $extensionName
	 * @param array $arguments
	 *
	 * @return NULL|string
	 */
	protected function translateErrorMessage($translateKey, $extensionName, $arguments = array()) {
		return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
			$translateKey,
			$extensionName,
			$arguments
		);
	}
}
