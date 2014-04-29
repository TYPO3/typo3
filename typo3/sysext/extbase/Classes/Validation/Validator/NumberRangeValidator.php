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
