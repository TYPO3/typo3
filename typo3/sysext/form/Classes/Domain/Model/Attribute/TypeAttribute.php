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
 * Attribute 'type'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TypeAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * @var array
	 */
	protected $allowedValues = array(
		'text',
		'password',
		'checkbox',
		'radio',
		'submit',
		'reset',
		'file',
		'hidden',
		'image',
		'button'
	);

	/**
	 * Gets the attribute 'type'.
	 * Used with all input elements
	 * Case Insensitive
	 *
	 * Defines the type of form input control to create.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$attribute = strtolower((string) $this->value);
		if (empty($attribute) || !in_array($attribute, $this->allowedValues)) {
			$attribute = 'text';
		}
		return $attribute;
	}

}
