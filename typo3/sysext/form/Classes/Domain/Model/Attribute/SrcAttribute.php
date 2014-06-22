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
 * Attribute 'src'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class SrcAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'src'.
	 * Used with the element 'input'
	 * URI type definition
	 *
	 * When the type attribute has the value "image", this attribute
	 * specifies the location of the image to be used to decorate the
	 * graphical submit button.
	 *
	 * @return string Attribute value
	 * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource()
	 */
	public function getValue() {
		$attribute = $this->localCobj->IMG_RESOURCE($this->value);
		return $attribute;
	}

}
