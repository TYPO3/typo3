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
 * Attribute 'maxlength'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class MaxlengthAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'maxlength'.
	 * Used with element 'input'
	 * Not subject to case changes, because it's an integer
	 *
	 * When the type attribute has the value "text" or "password",
	 * this attribute specifies the maximum number of characters
	 * the user may enter. This number may exceed the specified size,
	 * in which case the user agent should offer a scrolling mechanism.
	 * The default value for this attribute is an unlimited number.
	 *
	 * @return integer Attribute value
	 */
	public function getValue() {
		$value = (int)$this->value;
		if ($value <= 0) {
			$attribute = 40;
		} else {
			$attribute = $value;
		}
		return $attribute;
	}

}
