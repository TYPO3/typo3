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
 * Additional 'error'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ErrorAdditionalElement extends \TYPO3\CMS\Form\Domain\Model\Additional\AbstractAdditionalElement {

	/**
	 * Return the value of the object
	 *
	 * @return string
	 */
	public function getValue() {
		$errors = array();
		foreach ($this->value as $error) {
			$errors[] = $this->localCobj->cObjGetSingle($this->type, $error);
		}
		$value = implode(' - ', $errors);
		return $value;
	}

}
