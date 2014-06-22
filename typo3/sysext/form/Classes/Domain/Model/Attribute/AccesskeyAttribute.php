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
 * Attribute 'accesskey'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AccesskeyAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'accesskey'.
	 * Used with the element 'button, input, label, legend, textarea'
	 * Not subject to case changes
	 *
	 * This attribute assigns an access key to an element.
	 * An access key is a single character from the document character set.
	 * Note. Authors should consider the input method of the expected reader
	 * when specifying an accesskey.
	 *
	 * Pressing an access key assigned to an element gives focus to the element.
	 * The action that occurs when an element receives focus depends on the element.
	 * For example, when a user activates a link defined by the A element,
	 * the user agent generally follows the link. When a user activates a radio
	 * button, the user agent changes the value of the radio button.
	 * When the user activates a text field, it allows input, etc.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}
