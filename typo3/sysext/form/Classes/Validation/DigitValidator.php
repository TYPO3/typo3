<?php
namespace TYPO3\CMS\Form\Validation;

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
 * Digit rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class DigitValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * @var \TYPO3\CMS\Form\Filter\DigitFilter
	 */
	protected $filter;

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_digit';

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);
			if ($this->filter === NULL) {
				$className = 'TYPO3\\CMS\\Form\\Filter\\DigitFilter';
				$this->filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
			}
			if ($this->filter->filter($value) !== $value) {
				return FALSE;
			}
		}
		return TRUE;
	}

}