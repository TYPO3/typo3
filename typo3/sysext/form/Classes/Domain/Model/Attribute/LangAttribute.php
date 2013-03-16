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
 * Attribute 'lang'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class LangAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'lang'.
	 * Used with all elements
	 * Case Insensitive
	 *
	 * This attribute specifies the base language of an element's attribute
	 * values and text content. The default value of this attribute is unknown.
	 *
	 * Briefly, language codes consist of a primary code
	 * and a possibly empty series of subcodes:
	 *
	 * language-code = primary-code ( "-" subcode )
	 *
	 * Here are some sample language codes:
	 * "en": English
	 * "en-US": the U.S. version of English.
	 * "en-cockney": the Cockney version of English.
	 * "i-navajo": the Navajo language spoken by some Native Americans.
	 * "x-klingon": The primary tag "x" indicates an experimental language tag
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}

?>