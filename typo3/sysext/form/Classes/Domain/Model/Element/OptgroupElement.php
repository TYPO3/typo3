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
 * Optgroup model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class OptgroupElement extends \TYPO3\CMS\Form\Domain\Model\Element\ContainerElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class' => '',
		'disabled' => '',
		'id' => '',
		'label' => 'optgroup',
		'lang' => '',
		'style' => '',
		'title' => ''
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array();

	/**
	 * Set the name of the parent object
	 *
	 * @param string $parentName Name of the parent
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\OptgroupElement The element object
	 * @see \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement::setParent()
	 */
	public function setParentName($parentName) {
		/** @var $element \TYPO3\CMS\Form\Domain\Model\Element\OptionElement */
		foreach ($this->elements as $element) {
			$element->setParentName($parentName);
		}
		return $this;
	}

	/**
	 * Add child object to this element
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\OptionElement $element The child object
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\OptgroupElement
	 */
	public function addElement(\TYPO3\CMS\Form\Domain\Model\Element\OptionElement $element) {
		$this->elements[] = $element;
		return $this;
	}

}
