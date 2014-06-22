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
 * Validator for general numbers
 *
 * @api
 */
class NumberRangeValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'minimum' => array(0, 'The minimum value to accept', 'integer'),
		'maximum' => array(PHP_INT_MAX, 'The maximum value to accept', 'integer'),
		'startRange' => array(0, 'The minimum value to accept', 'integer'),
		'endRange' => array(PHP_INT_MAX, 'The maximum value to accept', 'integer')
	);

	/**
	 * The given value is valid if it is a number in the specified range.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	public function isValid($value) {
		if (!is_numeric($value)) {
			$this->addError(
				$this->translateErrorMessage(
					'validator.numberrange.notvalid',
					'extbase'
				), 1221563685);
			return;
		}

		/**
		 * @todo: remove this fallback to startRange/endRange in 6.3 when the setOptions() method is removed too
		 * @deprecated since Extbase 1.4, will be removed two versions after Extbase 6.1
		 */
		if (isset($this->options['minimum'])) {
			$minimum = $this->options['minimum'];
		} elseif (isset($this->options['startRange'])) {
			$minimum = $this->options['startRange'];
		} else {
			$minimum = 0;
		}
		if (isset($this->options['maximum'])) {
			$maximum = $this->options['maximum'];
		} elseif (isset($this->options['endRange'])) {
			$maximum = $this->options['endRange'];
		} else {
			$maximum = PHP_INT_MAX;
		}

		if ($minimum > $maximum) {
			$x = $minimum;
			$minimum = $maximum;
			$maximum = $x;
		}
		if ($value < $minimum || $value > $maximum) {
			$this->addError($this->translateErrorMessage(
				'validator.numberrange.range',
				'extbase',
				array(
					$minimum,
					$maximum
				)
			), 1221561046, array($minimum, $maximum));
		}
	}
}
