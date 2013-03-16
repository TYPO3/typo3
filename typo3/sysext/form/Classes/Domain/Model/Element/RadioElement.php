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

?>