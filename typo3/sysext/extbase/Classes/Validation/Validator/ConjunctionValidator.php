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
 * Validator to chain many validators in a conjunction (logical and). So every
 * validator has to be valid, to make the whole conjunction valid.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ConjunctionValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractCompositeValidator {

	/**
	 * Checks if the given value is valid according to the validators of the conjunction.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 * @api
	 */
	public function validate($value) {
		$result = new \TYPO3\CMS\Extbase\Error\Result();
		foreach ($this->validators as $validator) {
			$result->merge($validator->validate($value));
		}
		return $result;
	}

	/**
	 * Checks if the given value is valid according to the validators of the conjunction.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function isValid($value) {
		$result = TRUE;
		foreach ($this->validators as $validator) {
			if ($validator->isValid($value) === FALSE) {
				$this->errors = array_merge($this->errors, $validator->getErrors());
				$result = FALSE;
			}
		}
		return $result;
	}
}

?>