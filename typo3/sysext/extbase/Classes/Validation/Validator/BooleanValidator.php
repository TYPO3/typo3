<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Pascal Dürsteler <pascal.duersteler@gmail.com>
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
 * Validator for boolean values
 */
class BooleanValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE if the given property value is a boolean matching the expectation.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * Also testing for '1' (true), '0' and '' (false) because casting varies between
	 * tests and actual usage. This makes the validator loose but still keeping functionality.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is within the range, otherwise FALSE
	 */
	public function isValid($value) {
		if (!isset($this->options['is'])) {
			return TRUE;
		}
		switch (strtolower((string)$this->options['is'])) {
			case 'true':
			case '1':
				$expectation = TRUE;
				break;
			case 'false':
			case '':
			case '0':
				$expectation = FALSE;
				break;
			default:
				$this->addError('The given expectation is not valid.', 1361959227);
				return FALSE;
		}

		if ($value === $expectation) {
			return TRUE;
		} else {
			if (!is_bool($value)) {
				$this->addError('The given subject is not true.', 1361959230);
			} else {
				if ($expectation) {
					$this->addError('The given subject is not true.', 1361959228);
				} else {
					$this->addError('The given subject is not false.', 1361959229);
				}
			}
			return FALSE;
		}
	}

}

?>