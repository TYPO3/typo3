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
 * Attribute 'cols'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ColsAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'cols'.
	 * Used with the element 'textarea'
	 * Not subject to case changes, because it's an integer
	 *
	 * This attribute specifies the visible width in average character widths.
	 * Users should be able to enter longer lines than this,
	 * so user agents should provide some means to scroll through the contents
	 * of the control when the contents extend beyond the visible area.
	 * User agents may wrap visible text lines to keep long lines visible
	 * without the need for scrolling.
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
