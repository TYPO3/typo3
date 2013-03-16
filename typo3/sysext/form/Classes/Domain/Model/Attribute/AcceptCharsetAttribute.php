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
 * Attribute 'acceptcharset'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AcceptCharsetAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'accept-charset'.
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
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}

?>