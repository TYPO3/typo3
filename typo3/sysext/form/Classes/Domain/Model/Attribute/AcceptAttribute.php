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

?>