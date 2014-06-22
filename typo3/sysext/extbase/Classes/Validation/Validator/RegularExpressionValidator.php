<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
