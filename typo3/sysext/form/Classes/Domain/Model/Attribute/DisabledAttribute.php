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
 * Attribute 'disabled'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class DisabledAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'disabled'.
	 * Used with the elements button, input, optgroup, option, select & textarea
	 * Case Insensitive
	 *
	 * When set for a form control, this boolean attribute disables the control
	 * for user input.
	 *
	 * When set, the disabled attribute has the following effects on an element:
	 * Disabled controls do not receive focus.
	 * Disabled controls are skipped in tabbing navigation.
	 * Disabled controls cannot be successful.
	 *
	 * This attribute is inherited but local declarations override the inherited value.
	 *
	 * How disabled elements are rendered depends on the user agent.
	 * For example, some user agents "gray out" disabled menu items,
	 * button labels, etc.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		if (((int)$this->value === 1 || (bool) $this->value === TRUE) || strtolower((string) $this->value) === 'disabled') {
			$attribute = 'disabled';
		}
		return $attribute;
	}

}
