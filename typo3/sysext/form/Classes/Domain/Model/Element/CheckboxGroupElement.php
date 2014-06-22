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
 * Checkbox group model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class CheckboxGroupElement extends \TYPO3\CMS\Form\Domain\Model\Element\FieldsetElement {

	/**
	 * Add child object to this element
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element The child object
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\CheckboxGroupElement
	 */
	public function addElement(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element) {
		if ($element->acceptsParentName()) {
			$element->setName($this->getName());
			$element->attributes->setValue('name', $this->getName());
			$element->checkFilterAndSetIncomingDataFromRequest();
		}
		$this->elements[] = $element;
		return $this;
	}

}
