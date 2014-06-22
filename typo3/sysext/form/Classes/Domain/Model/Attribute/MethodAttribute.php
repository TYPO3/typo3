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
 * Attribute 'method'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class MethodAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Sets the attribute 'method'.
	 * Used with element 'form'
	 * Case Insensitive
	 *
	 * This attribute specifies which HTTP method will be used
	 * to submit the form data set.
	 * Possible (case-insensitive) values are "get" (the default) and "post".
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = strtolower((string) $this->value);
		if ($value == 'post' || $value == 'get') {
			$attribute = $value;
		} else {
			$attribute = 'post';
		}
		return $attribute;
	}

}
