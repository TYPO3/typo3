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
 * Attribute 'multiple'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class MultipleAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'multiple'.
	 * Used with element 'select'
	 * Case Insensitive
	 *
	 * If set, this boolean attribute allows multiple selections.
	 * If not set, the SELECT element only permits single selections.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		if (((int)$this->value === 1 || (bool)$this->value === TRUE) || strtolower((string)$this->value) === 'multiple') {
			$attribute = 'multiple';
		}
		return $attribute;
	}

}
