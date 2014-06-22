<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

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
 * Select model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class SelectElement extends \TYPO3\CMS\Form\Domain\Model\Element\ContainerElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'multiple' => '',
		'name' => '',
		'size' => '',
		'style' => '',
		'tabindex' => '',
		'title' => ''
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'name',
		'id'
	);

	/**
	 * Add child object to this element
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\OptionElement $element The child object
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\SelectElement
	 */
	public function addElement(\TYPO3\CMS\Form\Domain\Model\Element\OptionElement $element) {
		$element->setParentName($this->getName());
		$this->elements[] = $element;
		return $this;
	}

	/**
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\SelectElement
	 */
	public function setAttribute($attribute, $value) {
		if (array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}
		if (
			$attribute === 'name' && $this->attributes->hasAttribute('multiple') &&
			$this->attributes->getValue('multiple') === 'multiple' ||
			$attribute === 'multiple' && $this->attributes->hasAttribute('name')
		) {
			/** @var $nameAttribute \TYPO3\CMS\Form\Domain\Model\Attribute\NameAttribute */
			$nameAttribute = $this->attributes->getAttributeObjectByKey('name');
			$nameAttribute->setAddition('[]');
		}
		return $this;
	}

}
