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
 * Radio model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class RadioElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'alt' => '',
		'checked' => '',
		'class' => '',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'name' => '',
		'style' => '',
		'tabindex' => '',
		'title' => '',
		'type' => 'radio',
		'value' => ''
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
	 * True if it accepts the parent name instead of its own
	 * This is necessary for groups
	 *
	 * @var boolean
	 */
	protected $acceptsParentName = TRUE;

	/**
	 * Set the value of the radiobutton
	 *
	 * If there is submitted data for this field
	 * it will change the checked attribute
	 *
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\RadioElement
	 * @see \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement::checkFilterAndSetIncomingDataFromRequest()
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		if ($this->value === '') {
			$this->value = (string) $this->getElementId();
			$this->setAttribute('value', $this->value);
		}
		if ($this->requestHandler->has($this->getName())) {
			$submittedValue = $this->requestHandler->getByMethod($this->getName());
			if ($submittedValue === $this->value) {
				$this->setAttribute('checked', 'checked');
			}
		} elseif ($this->requestHandler->hasRequest()) {
			$this->attributes->removeAttribute('checked');
		}
		return $this;
	}

}
