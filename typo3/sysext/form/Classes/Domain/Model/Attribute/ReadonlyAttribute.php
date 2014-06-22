<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

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
 * Attribute 'readonly'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ReadonlyAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'readonly'.
	 * Used with the elements input and textarea
	 * Case Insensitive
	 *
	 * When set for a form control, this boolean attribute prohibits changes
	 * to the control.
	 *
	 * The readonly attribute specifies whether the control may be modified by the user.
	 *
	 * When set, the readonly attribute has the following effects on an element:
	 * Read-only elements receive focus but cannot be modified by the user.
	 * Read-only elements are included in tabbing navigation.
	 * Read-only elements may be successful.
	 *
	 * How read-only elements are rendered depends on the user agent.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		if (((int)$this->value === 1 || (bool)$this->value === TRUE) || strtolower((string)$this->value) === 'readonly') {
			$attribute = 'readonly';
		}
		return $attribute;
	}

}
