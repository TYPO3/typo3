<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Validator for general numbers
 *
 * @version $ID:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Validation_Validator_NumberRange {

	/**
	 * The start value of the range
	 * @var number
	 */
	protected $startRange;

	/**
	 * The end value of the range
	 * @var number
	 */
	protected $endRange;

	/**
	 * Constructor
	 *
	 * @param number The start of the range
	 * @param number The end of the range
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($startRange, $endRange) {
		if ($startRange > $endRange) {
			$this->endRange = $startRange;
			$this->startRange = $endRange;
		} else {
			$this->endRange = $endRange;
			$this->startRange = $startRange;
		}
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid number in the given range.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $propertyValue The value that should be validated
	 * @param TX_EXTMVC_Validation_Errors $errors Any occured Error will be stored here
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidProperty($propertyValue, TX_EXTMVC_Validation_Errors &$errors) {
		if (!is_numeric($propertyValue)) $errors->append('The given subject was not a valid number.');
		if ($propertyValue < $this->startRange || $propertyValue > $this->endRange) $errors->append('The given subject was not in the valid range (' . $this->startRange . ', ' . $this->endRange . ').');
		if (count($errors) > 0) return FALSE;
		return TRUE;
	}
}

?>