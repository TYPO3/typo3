<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Attribute 'tabindex'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TabindexAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'tabindex'.
	 * Used with the elements button, input, select and textarea
	 * Not subject to case changes
	 *
	 * This attribute specifies the position of the current element in the
	 * tabbing order for the current document. This value must be a number
	 * between 0 and 32767. User agents should ignore leading zeros.
	 *
	 * The tabbing order defines the order in which elements will receive focus
	 * when navigated by the user via the keyboard. The tabbing order may
	 * include elements nested within other elements.
	 *
	 * Elements that may receive focus should be navigated by user agents
	 * according to the following rules:
	 * 1. Those elements that support the tabindex attribute and assign a
	 * positive value to it are navigated first. Navigation proceeds from the
	 * element with the lowest tabindex value to the element with the highest
	 * value. Values need not be sequential nor must they begin with any
	 * particular value. Elements that have identical tabindex values should
	 * be navigated in the order they appear in the character stream.
	 * 2. Those elements that do not support the tabindex attribute or support
	 * it and assign it a value of "0" are navigated next. These elements are
	 * navigated in the order they appear in the character stream.
	 * 3. Elements that are disabled do not participate in the tabbing order.
	 *
	 * The actual key sequence that causes tabbing navigation or element
	 * activation depends on the configuration of the user agent
	 * (e.g., the "tab" key is used for navigation and the "enter" key is used
	 * to activate a selected element)
	 *
	 * User agents may also define key sequences to navigate the tabbing order
	 * in reverse. When the end (or beginning) of the tabbing order is reached,
	 * user agents may circle back to the beginning (or end).
	 *
	 * @return integer Attribute value
	 */
	public function getValue() {
		$attribute = (int) $this->value;
		if ($attribute < 0 || $attribute > 32767) {
			$attribute = 0;
		}
		return $attribute;
	}

}

?>