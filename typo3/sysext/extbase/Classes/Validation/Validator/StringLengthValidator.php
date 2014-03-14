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
 * Validator for string length.
 *
 * @api
 */
class StringLengthValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'minimum' => array(0, 'Minimum length for a valid string', 'integer'),
		'maximum' => array(PHP_INT_MAX, 'Maximum length for a valid string', 'integer')
	);

	/**
	 * Checks if the given value is a valid string (or can be cast to a string
	 * if an object is given) and its length is between minimum and maximum
	 * specified in the validation options.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
	 * @api
	 */
	public function isValid($value) {
		if ($this->options['maximum'] < $this->options['minimum']) {
			throw new \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException('The \'maximum\' is shorter than the \'minimum\' in the StringLengthValidator.', 1238107096);
		}

		if (is_object($value)) {
			if (!method_exists($value, '__toString')) {
				$this->addError('The given object could not be converted to a string.', 1238110957);
				return;
			}
		} elseif (!is_string($value)) {
			$this->addError('The given value was not a valid string.', 1269883975);
			return;
		}

		// TODO Use \TYPO3\CMS\Core\Charset\CharsetConverter::strlen() instead; How do we get the charset?
		$stringLength = strlen($value);
		$isValid = TRUE;
		if ($stringLength < $this->options['minimum']) {
			$isValid = FALSE;
		}
		if ($stringLength > $this->options['maximum']) {
			$isValid = FALSE;
		}

		if ($isValid === FALSE) {
			if ($this->options['minimum'] > 0 && $this->options['maximum'] < PHP_INT_MAX) {
				$this->addError(
					$this->translateErrorMessage(
						'validator.stringlength.between',
						'extbase',
						array (
							$this->options['minimum'],
							$this->options['maximum']
						)
					), 1238108067, array($this->options['minimum'], $this->options['maximum']));
			} elseif ($this->options['minimum'] > 0) {
				$this->addError(
					$this->translateErrorMessage(
						'validator.stringlength.less',
						'extbase',
						array(
							$this->options['minimum']
						)
					), 1238108068, array($this->options['minimum']));
			} else {
				$this->addError(
					$this->translateErrorMessage(
						'validator.stringlength.exceed',
						'extbase',
						array(
							$this->options['maximum']
						)
					), 1238108069, array($this->options['maximum']));
			}
		}
	}
}
