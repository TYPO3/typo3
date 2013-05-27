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
 * Abstract validator
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 */
abstract class Tx_Extbase_Validation_Validator_AbstractValidator implements Tx_Extbase_Validation_Validator_ValidatorInterface {
	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0. You should use constructor parameter to set validation options.
	 */
	protected $errors = array();

	/**
	 * @var Tx_Extbase_Error_Result
	 */
	protected $result;

	/**
	 * Sets options for the validator
	 *
	 * @param array $validationOptions Options for the validator
	 * @return void
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
	 * @return Tx_Extbase_Error_Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new Tx_Extbase_Error_Result();
		$this->isValid($value);
		return $this->result;
	}

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @return void
	 */
	abstract protected function isValid($value);

	/**
	 * Sets options for the validator
	 *
	 * @param array $options Options for the validator
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0. use constructor instead.
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of Tx_Extbase_Validation_Error objects or an empty array if no errors occurred.
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0. use validate() instead.
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Creates a new validation error object and adds it to $this->errors
	 *
	 * @param string $message The error message
	 * @param integer $code The error code (a unix timestamp)
	 * @return void
	 */
	protected function addError($message, $code) {
		if ($this->result !== NULL) {
				// backwards compatibility before Extbase 1.4.0: we cannot expect the "result" object to be there.
			$this->result->addError(new Tx_Extbase_Validation_Error($message, $code));
		}
		// the following is @deprecated since Extbase 1.4.0:
		$this->errors[] = new Tx_Extbase_Validation_Error($message, $code);
	}
}

?>