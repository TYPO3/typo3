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
 * Attribute 'title'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TitleAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'title'.
	 * Used with all elements
	 * Case Sensitive
	 *
	 * This attribute offers advisory information about the element for which it is set.
	 * Unlike the TITLE element, which provides information about an entire
	 * document and may only appear once, the title attribute may annotate any
	 * number of elements. Please consult an element's definition to verify that
	 * it supports this attribute.
	 *
	 * Values of the title attribute may be rendered by user agents in a variety
	 * of ways. For instance, visual browsers frequently display the title as a
	 * "tool tip" (a short message that appears when the pointing device pauses
	 * over an object). Audio user agents may speak the title information in a
	 * similar context.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = (string) $this->value;
		return $attribute;
	}

}
