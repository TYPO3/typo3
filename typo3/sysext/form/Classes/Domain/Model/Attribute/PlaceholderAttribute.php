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
 * Attribute 'placeholder'
 */
class PlaceholderAttribute extends AbstractAttribute {

	/**
	 * Gets the attribute 'placeholder'.
	 * Used with textline element
	 *
	 * This attribute assigns a placeholder to an element.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		return (string)$this->value;
	}

	/**
	 * Set the value
	 *
	 * @param mixed $value The value to set
	 * @return void
	 */
	public function setValue($value) {
		if (is_string($value)) {
			$this->value = $value;
		} elseif (is_array($value)) {
			$this->value = $this->localCobj->cObjGetSingle('TEXT', $value);
		} else {
			$this->value = '';
		}
	}
}