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
		$value = (int) $this->value;
		if ($value <= 0) {
			$attribute = 40;
		} else {
			$attribute = $value;
		}
		return $attribute;
	}

}

?>