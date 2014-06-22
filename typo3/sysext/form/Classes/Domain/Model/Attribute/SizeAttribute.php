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
 * Attribute 'size'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class SizeAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'size'.
	 * Used with the element 'input'
	 * Not subject to case changes, because it's an integer
	 *
	 * This attribute tells the user agent the initial width of the control.
	 * The width is given in pixels except when type attribute
	 * has the value "text" or "password".
	 * In that case, its value refers to the (integer) number of characters.
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
