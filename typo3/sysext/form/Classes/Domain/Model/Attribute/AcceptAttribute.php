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
 * Attribute 'accept'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AcceptAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'accept'.
	 * Used with the elements 'form', 'input'
	 * Case Insensitive
	 *
	 * This attribute specifies a comma-separated list of content types
	 * that a server processing this form will handle correctly.
	 * User agents may use this information to filter out non-conforming files
	 * when prompting a user to select files to be sent to the server (cf. the INPUT element when type="file").
	 * RFC2045: For a complete list, see http://www.iana.org/assignments/media-types/
	 *
	 * TODO: Perhaps we once can add a list of all content-types to TYPO3
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}
