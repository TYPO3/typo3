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
 * Contract for a validator
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $ID:$
 */
interface Tx_Extbase_Validation_Validator_ValidatorInterface {

	/**
	 * Checks if the given value is valid according to the validator.
	 *
	 * If at least one error occurred, the result is FALSE and any errors can
	 * be retrieved through the getErrors() method.
	 *
	 * Note that all implementations of this method should set $this->errors() to an
	 * empty array before validating.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value);

	/**
	 * Sets validation options for the validator
	 *
	 * @param array $validationOptions The validation options
	 * @return void
	 */
	public function setOptions(array $validationOptions);

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of error messages or an empty array if no errors occurred.
	 */
	public function getErrors();

}

?>