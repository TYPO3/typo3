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
 * Attribute 'acceptcharset'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_attributes_acceptcharset extends tx_form_domain_model_attributes_abstract implements tx_form_domain_model_attributes_interface {

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
	 * Sets the attribute 'accept-charset'.
	 * Used with the element 'form'
	 * Case Insensitive
	 *
	 * This attribute specifies the list of character encodings for input data
	 * that is accepted by the server processing this form.
	 * The value is a space- and/or comma-delimited list of charset values.
	 * The client must interpret this list as an exclusive-or list, i.e.,
	 * the server is able to accept any single character encoding per entity received.
	 *
	 * The default value for this attribute is the reserved string "UNKNOWN".
	 * User agents may interpret this value as the character encoding that was
	 * used to transmit the document containing this FORM element.
	 *
	 * RFC2045: For a complete list, see http://www.iana.org/assignments/character-sets/
	 *
	 * TODO: Perhaps we once can add a list of all character-sets to TYPO3
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