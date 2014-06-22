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
 * Validator to chain many validators in a conjunction (logical and).
 *
 * @api
 */
class ConjunctionValidator extends AbstractCompositeValidator {

	/**
	 * Checks if the given value is valid according to the validators of the conjunction.
	 * Every validator has to be valid, to make the whole conjunction valid.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 * @api
	 */
	public function validate($value) {
		$validators = $this->getValidators();
		if ($validators->count() > 0) {
			$result = NULL;
			foreach ($validators as $validator) {
				if ($result === NULL) {
					$result = $validator->validate($value);
				} else {
					$result->merge($validator->validate($value));
				}
			}
		} else {
			$result = new \TYPO3\CMS\Extbase\Error\Result;
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
