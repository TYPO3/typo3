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
 * Validator for email addresses
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: EmailAddressValidator.php 1792 2010-01-19 00:06:05Z jocrau $
 */
class Tx_Extbase_Validation_Validator_EmailAddressValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * Checks if the given value is a valid email address.
	 * If at least one error occurred, the result is FALSE.
	 * 
	 * The regexp is a modified version of the last one shown on
	 * http://www.regular-expressions.info/email.html
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		$this->errors = array();
		if(is_string($value) && preg_match('
				/
					^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*
					@
					(?:
						(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[a-z]{2}|aero|asia|biz|cat|com|edu|coop|gov|info|int|invalid|jobs|localdomain|mil|mobi|museum|name|net|org|pro|tel|travel)|
						localhost|
						(?:(?:\d{1,2}|1\d{1,2}|2[0-5][0-5])\.){3}(?:(?:\d{1,2}|1\d{1,2}|2[0-5][0-5]))
					)
					\b
				/ix', $value)) return TRUE;
		$this->addError('The given subject was not a valid email address.', 1221559976);
		return FALSE;
	}
}

?>