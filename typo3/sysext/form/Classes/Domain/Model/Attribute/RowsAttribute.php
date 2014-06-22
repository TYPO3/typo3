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
 * Attribute 'rows'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class RowsAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'rows'.
	 * Used with the element 'textarea'
	 * Not subject to case changes, because it's an integer
	 *
	 * This attribute specifies the number of visible text lines.
	 * Users should be able to enter more lines than this,
	 * so user agents should provide some means to scroll
	 * through the contents of the control when the contents extend
	 * beyond the visible area.
	 *
	 * @return integer Attribute value
	 */
	public function getValue() {
		$value = (int)$this->value;
		if ($value <= 0) {
			$attribute = 80;
		} else {
			$attribute = $value;
		}
		return $attribute;
	}

}
