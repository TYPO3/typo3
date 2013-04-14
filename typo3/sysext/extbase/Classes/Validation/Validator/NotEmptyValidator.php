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
 * Validator for not empty values
 */
class NotEmptyValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {

	/**
	 * Checks if the given property ($propertyValue) is not empty (NULL, empty string, empty array or empty object).
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		$this->errors = array();
		if ($value === NULL) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.notempty.null',
					'extbase'
				), 1221560910);
			return FALSE;
		}
		if ($value === '') {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.notempty.empty',
					'extbase'
				), 1221560718);
			return FALSE;
		}
		if (is_array($value) && empty($value)) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.notempty.empty',
					'extbase'
				), 1347992400);
			return FALSE;
		}
		if (is_object($value) && $value instanceof \Countable && $value->count() === 0) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.notempty.empty',
					'extbase'
				), 1347992453);
			return FALSE;
		}
		return TRUE;
	}
}

?>