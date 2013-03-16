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

?>