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
 * Attribute 'dir'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class DirAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'dir'.
	 * Used with all elements
	 * Case Insensitive
	 *
	 * This attribute specifies the base direction of directionally neutral text
	 * (i.e., text that doesn't have inherent directionality as defined in
	 * [UNICODE]) in an element's content and attribute values.
	 * It also specifies the directionality of tables. Possible values:
	 *
	 * LTR: Left-to-right text or table.
	 * RTL: Right-to-left text or table.
	 *
	 * In addition to specifying the language of a document with the lang
	 * attribute, authors may need to specify the base directionality
	 * (left-to-right or right-to-left) of portions of a document's text,
	 * of table structure, etc. This is done with the dir attribute.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = strtolower((string) $this->value);
		if ($value == 'ltr' || $value == 'rtl') {
			$attribute = $value;
		}
		return $attribute;
	}

}
