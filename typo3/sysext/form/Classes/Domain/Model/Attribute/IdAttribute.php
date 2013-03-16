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
 * Attribute 'id'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class IdAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'id'.
	 * Used with all elements
	 * Case Sensitive
	 *
	 * This attribute assigns an id to an element.
	 * This id must be unique in a document.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = (string) $this->value;
		if ($this->elementClassName === 'TYPO3\\CMS\\Form\\Domain\\Model\\Form') {
			if (empty($value)) {
				$value = 'form-' . $GLOBALS['TSFE']->id;
			}
		} elseif (empty($value)) {
			$value = $this->elementId;
			if (is_integer($value)) {
				$value = 'field-' . $value;
			}
		}
		// Change spaces into hyphens
		$attribute = preg_replace('/\\s/', '-', $value);
		// Change first non-letter to field-
		if (preg_match('/^([^a-zA-Z]{1})/', $attribute)) {
			$attribute = 'field-' . $attribute;
		}
		// Remove non-word characters
		$attribute = preg_replace('/([^a-zA-Z0-9_:\\-\\.]*)/', '', $attribute);
		return $attribute;
	}

}

?>