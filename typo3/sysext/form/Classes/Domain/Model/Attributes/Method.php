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
 * Attribute 'method'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Attributes_Method extends tx_form_Domain_Model_Attributes_Abstract {
	/**
	 * Sets the attribute 'method'.
	 * Used with element 'form'
	 * Case Insensitive
	 *
	 * This attribute specifies which HTTP method will be used
	 * to submit the form data set.
	 * Possible (case-insensitive) values are "get" (the default) and "post".
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = strtolower((string) $this->value);

		if ($value == 'post' || $value == 'get') {
			$attribute = $value;
		} else {
			$attribute = 'post';
		}

		return $attribute;
	}
}
?>