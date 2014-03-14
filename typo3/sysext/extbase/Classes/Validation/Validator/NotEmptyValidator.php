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

/**
 * Validator for not empty values.
 *
 * @api
 */
class NotEmptyValidator extends AbstractValidator {

	/**
	 * This validator always needs to be executed even if the given value is empty.
	 * See AbstractValidator::validate()
	 *
	 * @var boolean
	 */
	protected $acceptsEmptyValues = FALSE;

	/**
	 * Checks if the given property ($propertyValue) is not empty (NULL, empty string, empty array or empty object).
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occurred
	 */
	public function isValid($value) {
		if ($value === NULL) {
			$this->addError(
				$this->translateErrorMessage(
					'validator.notempty.null',
					'extbase'
				), 1221560910);
		}
		if ($value === '') {
			$this->addError(
				$this->translateErrorMessage(
					'validator.notempty.empty',
					'extbase'
				), 1221560718);
		}
		if (is_array($value) && empty($value)) {
			$this->addError(
				$this->translateErrorMessage(
					'validator.notempty.empty',
					'extbase'
				), 1347992400);
		}
		if (is_object($value) && $value instanceof \Countable && $value->count() === 0) {
			$this->addError(
				$this->translateErrorMessage(
					'validator.notempty.empty',
					'extbase'
				), 1347992453);
		}
	}
}
