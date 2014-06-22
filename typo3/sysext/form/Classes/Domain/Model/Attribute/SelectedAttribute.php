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
 * Attribute 'selected'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class SelectedAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'selected'
	 * Used with the element 'option'
	 * Case insensitive
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		if (((int)$this->value === 1 || (bool)$this->value === TRUE) || strtolower((string)$this->value) === 'selected') {
			$attribute = 'selected';
		}
		return $attribute;
	}

}
