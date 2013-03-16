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
 * Attribute 'checked'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class CheckedAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'checked'
	 * Used with the element 'input' only if type attribute has the value
	 * 'radio, checkbox'
	 * Case insensitive
	 *
	 * When the type attribute has the value "radio" or "checkbox",
	 * this boolean attribute specifies that the button is on.
	 * User agents must ignore this attribute for other control types.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		if (((int) $this->value === 1 || (bool) $this->value === TRUE) || strtolower((string) $this->value === 'checked')) {
			$attribute = 'checked';
		}
		return $attribute;
	}

}

?>