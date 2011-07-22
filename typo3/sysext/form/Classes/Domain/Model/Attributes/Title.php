<?php
declare(encoding = 'utf-8');

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
 * Attribute 'title'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_attributes_title extends tx_form_domain_model_attributes_abstract implements tx_form_domain_model_attributes_interface {

	/**
	 * Constructor
	 *
	 * @param string $value Attribute value
	 * @param integer $elementId The ID of the element
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($value, $elementId) {
		parent::__construct($value, $elementId);
	}

	/**
	 * Sets the attribute 'title'.
	 * Used with all elements
	 * Case Sensitive
	 *
	 * This attribute offers advisory information about the element for which it is set.
	 * Unlike the TITLE element, which provides information about an entire
	 * document and may only appear once, the title attribute may annotate any
	 * number of elements. Please consult an element's definition to verify that
	 * it supports this attribute.
	 *
	 * Values of the title attribute may be rendered by user agents in a variety
	 * of ways. For instance, visual browsers frequently display the title as a
	 * "tool tip" (a short message that appears when the pointing device pauses
	 * over an object). Audio user agents may speak the title information in a
	 * similar context.
	 *
	 * @return string Attribute value
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getValue() {
		$attribute = (string) $this->value;

		return $attribute;
	}
}
?>