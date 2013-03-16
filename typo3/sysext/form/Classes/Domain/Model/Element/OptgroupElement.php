<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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

?>