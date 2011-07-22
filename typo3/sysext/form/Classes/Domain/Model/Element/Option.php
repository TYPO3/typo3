<?php
declare(encoding = 'utf-8');

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
 *  Option model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_element_option extends tx_form_domain_model_element_abstract {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class' => '',
		'disabled' => '',
		'id' => '',
		'label' => '',
		'lang' => '',
		'selected' => '',
		'style' => '',
		'title' => '',
		'value' => '',
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
	);

	/**
	 * Parent object
	 *
	 * @var object
	 */
	protected $parentObject;

	/**
	 * Constructor
	 * Sets the configuration, calls parent constructor and fills the attributes
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Returns the content of the option tag
	 * <option>content</option>
	 *
	 * @return string
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Set the name of the parent object
	 *
	 * @param string $parentName Name of the parent
	 * @return object The element object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 * @see typo3/sysext/form/model/element/tx_form_domain_model_element#setParent()
	 */
	public function setParentName($parentName) {
		$this->parentName = $parentName;
		$this->checkFilterAndSetIncomingDataFromRequest();

		return $this;
	}

	/**
	 * Set the value of the option
	 *
	 * If there is submitted data for this field
	 * it will change the selected attribute
	 *
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 * @see typo3/sysext/form/model/element/tx_form_domain_model_element#checkFilterAndSetIncomingDataFromRequest()
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		if ($this->value === '') {
			$this->value = (string) $this->getElementId();
			$this->setAttribute('value', $this->value);
		}
		if(!empty($this->parentName)) {
			if($this->requestHandler->has($this->parentName)) {
				$submittedValue = $this->requestHandler->getByMethod($this->parentName);
				if(is_array($submittedValue) && in_array($this->value, $submittedValue)) {
					$this->setAttribute('selected', 'selected');
				} elseif($submittedValue === $this->value) {
					$this->setAttribute('selected', 'selected');
				} elseif(is_array($submittedValue) && in_array('on', $submittedValue)) {
					$this->setAttribute('selected', 'selected');
				} else {
					$this->attributes->removeAttribute('selected');
				}
			} elseif($this->requestHandler->hasRequest()) {
				$this->attributes->removeAttribute('selected');
			}
		}
		return $this;
	}
}
?>