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
