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
 * Class for the form container elements
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ContainerElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * Child elements of this object
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Add child object to this element
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element The child object
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\ContainerElement
	 */
	public function addElement(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element) {
		$this->elements[] = $element;
		return $this;
	}

	/**
	 * Get the child elements
	 *
	 * @return array Child objects
	 */
	public function getElements() {
		return $this->elements;
	}

}
