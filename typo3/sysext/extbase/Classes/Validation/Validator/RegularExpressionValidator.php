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
 * Validator based on regular expressions.
 *
 * @api
 */
class RegularExpressionValidator extends AbstractValidator {


	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'regularExpression' => array('', 'The regular expression to use for validation, used as given', 'string', TRUE)
	);

	/**
	 * Checks if the given value matches the specified regular expression.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 * @api
	 */
	public function isValid($value) {
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError(
				$this->translateErrorMessage(
					'validator.regularexpression.nomatch',
					'extbase'
				), 1221565130);
		}
		if ($result === FALSE) {
			throw new \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException('regularExpression "' . $this->options['regularExpression'] . '" in RegularExpressionValidator contained an error.', 1298273089);
		}
	}
}
