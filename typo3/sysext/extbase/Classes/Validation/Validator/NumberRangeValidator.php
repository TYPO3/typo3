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
 * Validator for general numbers
 */
class NumberRangeValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid number in the given range.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is within the range, otherwise FALSE
	 */
	public function isValid($value) {
		if (isset($this->options['minimum'])) {
			$this->options['startRange'] = $this->options['minimum'];
		}
		if (isset($this->options['maximum'])) {
			$this->options['endRange'] = $this->options['maximum'];
		}
		$this->errors = array();
		if (!is_numeric($value)) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.numberrange.notvalid',
					'extbase'
				), 1221563685);
			return FALSE;
		}
		$startRange = isset($this->options['startRange']) ? intval($this->options['startRange']) : 0;
		$endRange = isset($this->options['endRange']) ? intval($this->options['endRange']) : PHP_INT_MAX;
		if ($startRange > $endRange) {
			$x = $startRange;
			$startRange = $endRange;
			$endRange = $x;
		}
		if ($value >= $startRange && $value <= $endRange) {
			return TRUE;
		}
		$this->addError(
			\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
				'validator.numberrange.range',
				'extbase',
				array(
					$startRange,
					$endRange
				)
			), 1221561046, array($startRange, $endRange));
		return FALSE;
	}
}

?>