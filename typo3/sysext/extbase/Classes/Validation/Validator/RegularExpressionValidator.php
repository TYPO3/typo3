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
 * Validator based on regular expressions
 *
 * The regular expression is specified in the options by using the array key "regularExpression"
 */
class RegularExpressionValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) matches the given regular expression.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		$this->errors = array();
		if (!isset($this->options['regularExpression'])) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.regularexpression.empty',
					'extbase'
				), 1221565132);
			return FALSE;
		}
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.regularexpression.nomatch',
					'extbase'
				), 1221565130);
			return FALSE;
		}
		if ($result === FALSE) {
			$this->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
					'validator.regularexpression.error',
					'extbase',
					array(
						$this->options['regularExpression']
					)
				), 1221565131, array($this->options['regularExpression']));
			return FALSE;
		}
		return TRUE;
	}
}

?>