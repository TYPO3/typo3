<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Attribute 'readonly'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Attributes_Readonly extends tx_form_Domain_Model_Attributes_Abstract {
	/**
	 * Gets the attribute 'readonly'.
	 * Used with the elements input and textarea
	 * Case Insensitive
	 *
	 * When set for a form control, this boolean attribute prohibits changes
	 * to the control.
	 *
	 * The readonly attribute specifies whether the control may be modified by the user.
	 *
	 * When set, the readonly attribute has the following effects on an element:
	 * Read-only elements receive focus but cannot be modified by the user.
	 * Read-only elements are included in tabbing navigation.
	 * Read-only elements may be successful.
	 *
	 * How read-only elements are rendered depends on the user agent.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		if ((integer) $this->value === 1
			|| (boolean) $this->value === TRUE
			|| strtolower((string) $this->value) === 'readonly')
		{
			$attribute = 'readonly';
		}

		return $attribute;
	}
}
?>