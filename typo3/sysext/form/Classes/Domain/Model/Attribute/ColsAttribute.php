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