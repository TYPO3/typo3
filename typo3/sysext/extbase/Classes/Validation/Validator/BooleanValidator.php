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
 * Validator for boolean values
 */
class BooleanValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		// The default is set to NULL here, because we need to be backward compatible here, because this
		// BooleanValidator is called automatically on boolean action arguments. If we would set it to TRUE,
		// every FALSE value for an action argument would break.
		// TODO with next patches: deprecate this BooleanValidator and introduce a BooleanValueValidator, like
		// in Flow, which won't be called on boolean action arguments.
		'is' => array(NULL, 'Boolean value', 'boolean|string|integer')
	);


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
		// see comment above, check if expectation is NULL, then nothing to do!
		if ($this->options['is'] === NULL) {
			return;
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
				return;
		}

		if ($value !== $expectation) {
			if (!is_bool($value)) {
				$this->addError('The given subject is not true.', 1361959230);
			} else {
				if ($expectation) {
					$this->addError('The given subject is not true.', 1361959228);
				} else {
					$this->addError('The given subject is not false.', 1361959229);
				}
			}
		}
	}

}
