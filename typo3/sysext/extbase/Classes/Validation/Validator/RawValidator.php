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
 * A validator which accepts any input
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 */
class Tx_Extbase_Validation_Validator_RawValidator implements Tx_Extbase_Validation_Validator_ValidatorInterface {

	/**
	 * Always returns TRUE
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 1.6.0
	 */
	public function isValid($value) {
		return TRUE;
	}

	/**
	 * Sets options for the validator
	 *
	 * @param array $options Not used
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 1.6.0
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of error messages or an empty array if no errors occurred.
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 1.6.0
	 */
	public function getErrors() {
		return array();
	}

	/**
	 * Always returns TRUE
	 *
	 * @param mixed $value The value that should be validated
	 * @return \F3\FLOW3\Error\Result
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function validate($value) {
		return new Tx_Extbase_Error_Result();
	}

}
?>