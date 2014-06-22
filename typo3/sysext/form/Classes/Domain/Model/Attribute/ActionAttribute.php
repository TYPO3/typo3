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
 * Attribute 'action'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ActionAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'action'
	 * Used with the element 'form'
	 * Lower case
	 *
	 * This attribute specifies a form processing agent.
	 * User agent behavior for a value other than an HTTP URI is undefined.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = $this->value;
		if (empty($value)) {
			$value = $GLOBALS['TSFE']->id;
		}
		$attribute = $this->localCobj->getTypoLink_URL($value);
		return $attribute;
	}

}
