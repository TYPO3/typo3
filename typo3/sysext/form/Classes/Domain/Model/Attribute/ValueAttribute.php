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
 * Attribute 'value'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ValueAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'value'.
	 * Used with the elements input, option, button
	 * The element or attribute definition itself gives case information.
	 *
	 * button: This attribute assigns the initial value to the button.
	 *
	 * input: This attribute specifies the initial value of the control. It is
	 * optional except when the type attribute has the value
	 * "radio" or "checkbox".
	 *
	 * option: This attribute specifies the initial value of the control.
	 * If this attribute is not set, the initial value is set to the contents
	 * of the OPTION element.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}
