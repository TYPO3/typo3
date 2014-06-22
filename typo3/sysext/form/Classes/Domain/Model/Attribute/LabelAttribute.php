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
 * Attribute 'label'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class LabelAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Defines the label usage of the linked document.
	 * Used with optgroup and option
	 * Case Sensitive
	 *
	 * This attribute allows authors to specify a shorter label for an option
	 * than the content of the OPTION element. When specified, user agents
	 * should use the value of this attribute
	 * rather than the content of the OPTION element as the option label.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}
