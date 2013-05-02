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
 * Float rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FloatValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_float';

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);
			$locale = localeconv();
			$valueFiltered = str_replace(
				array(
					$locale['thousands_sep'],
					$locale['mon_thousands_sep'],
					$locale['decimal_point'],
					$locale['mon_decimal_point']
				),
				array(
					'',
					'',
					'.',
					'.'
				),
				$value
			);
			if ($valueFiltered != strval(floatval($valueFiltered))) {
				return FALSE;
			}
		}
		return TRUE;
	}

}
