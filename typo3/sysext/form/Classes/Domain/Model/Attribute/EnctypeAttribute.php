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
 * Attribute 'enctype'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class EnctypeAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'enctype'.
	 * Used with the element 'form'
	 * Case Insensitive
	 *
	 * This attribute specifies the content type used to submit the form to the
	 * server (when the value of method is "post"). The default value for this
	 * attribute is "application/x-www-form-urlencoded".
	 * The value "multipart/form-data" should be used in combination with the
	 * INPUT element, type="file".
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = strtolower((string) $this->value);
		if ($value == 'multipart/form-data' || $value == 'application/x-www-form-urlencoded') {
			$attribute = $value;
		} elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'])) {
			$attribute = $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'];
		}
		return $attribute;
	}

}
