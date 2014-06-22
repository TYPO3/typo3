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
