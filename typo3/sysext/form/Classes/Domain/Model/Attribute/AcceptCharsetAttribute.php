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
