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
 * Validator for general numbers
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: NumberRangeValidator.php 1789 2010-01-18 21:31:59Z jocrau $
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_NumberRangeValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid number in the given range.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is within the range, otherwise FALSE
	 */
	public function isValid($value) {
		$this->errors = array();
		if (!is_numeric($value)) {
			$this->addError('The given subject was not a valid number.', 1221563685);
			return FALSE;
		}

		$startRange = (isset($this->options['startRange'])) ? intval($this->options['startRange']) : 0;
		$endRange = (isset($this->options['endRange'])) ? intval($this->options['endRange']) : PHP_INT_MAX;
		if ($startRange > $endRange) {
			$x = $startRange;
			$startRange = $endRange;
			$endRange = $x;
		}
		if ($value >= $startRange && $value <= $endRange) return TRUE;

		$this->addError('The given subject was not in the valid range (' . $startRange . ' - ' . $endRange . ').', 1221561046);
		return FALSE;
	}
}

?>