<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Checkbox model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Element_Checkbox extends tx_form_Domain_Model_Element_Abstract {

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
		'type' => 'checkbox',
		'value' => '',
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
	 * Set the value of the checkbox
	 *
	 * If there is submitted data for this field
	 * it will change the checked attribute
	 *
	 * @return tx_form_Domain_Model_Element_Checkbox
	 * @see tx_form_Domain_Model_Element::checkFilterAndSetIncomingDataFromRequest()
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		if ($this->value === '') {
			$this->value = (string) $this->getElementId();
			$this->setAttribute('value', $this->value);
		}

		if ($this->requestHandler->has($this->getName())) {
			$submittedValue = $this->requestHandler->getByMethod($this->getName());
			if (is_array($submittedValue) && in_array($this->value, $submittedValue)) {
				$this->setAttribute('checked', 'checked');
			} elseif ($submittedValue === $this->value) {
				$this->setAttribute('checked', 'checked');
			} elseif (is_array($submittedValue) && in_array('on', $submittedValue)) {
				$this->setAttribute('checked', 'checked');
			}
		} elseif ($this->requestHandler->hasRequest()) {
			$this->attributes->removeAttribute('checked');
		}
		return $this;
	}

	/**
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return tx_form_Domain_Model_Element_Checkbox
	 */
	public function setAttribute($attribute, $value) {
		if (array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}

		if ($attribute === 'name') {
			/** @var $nameAttribute tx_form_Domain_Model_Attributes_Name */
			$nameAttribute = $this->attributes->getAttributeObjectByKey('name');
			$nameAttribute->setAddition('[]');
		}

		return $this;
	}
}
?>