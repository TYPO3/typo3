<?php
namespace TYPO3\CMS\Form\Domain\Model\Additional;

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
 * Additional 'mandatory'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class MandatoryAdditionalElement extends \TYPO3\CMS\Form\Domain\Model\Additional\AbstractAdditionalElement {

	/**
	 * Return the value of the object
	 *
	 * @return array
	 */
	public function getValue() {
		$messages = array();
		foreach ($this->value as $message) {
			$messages[] = $this->localCobj->cObjGetSingle($this->type, $message);
		}
		$value = implode(' - ', $messages);
		return $value;
	}

}
