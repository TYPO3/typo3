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
 * @version $Id: RawValidator.php 1789 2010-01-18 21:31:59Z jocrau $
 */
class Tx_Extbase_Validation_Validator_RawValidator implements Tx_Extbase_Validation_Validator_ValidatorInterface {

	/**
	 * Always returns TRUE
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE
	 */
	public function isValid($value) {
		return TRUE;
	}

	/**
	 * Sets options for the validator
	 *
	 * @param array $options Not used
	 * @return void
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of error messages or an empty array if no errors occurred.
	 */
	public function getErrors() {
		return array();
	}

}
?>