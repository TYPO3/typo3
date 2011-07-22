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
 * A form
 *
 * Takes the incoming Typoscipt and adds all the necessary form objects
 * according to the configuration.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_form extends tx_form_domain_model_element_container {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accept' => '',
		'accept-charset' => '',
		'action' => '',
		'class' => '',
		'dir' => '',
		'enctype' => 'application/x-www-form-urlencoded',
		'id' => '',
		'lang' => '',
		'method' => 'get',
		'name' => '',
		'style' => '',
		'title' => '',
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'method',
		'action'
	);

	/**
	 * Constructor
	 * Sets the configuration, calls parent constructor, fills the attributes
	 * and adds all form element objects
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAttribute($attribute, $value) {
		if(array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}

		if($attribute == 'id' || $attribute == 'name') {
			$this->equalizeNameAndIdAttribute();
		}

		return $this;
	}

	/**
	 * Makes the value of attributes 'name' and 'id' equal
	 * when both have been filled.
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function equalizeNameAndIdAttribute() {
		$nameAttribute = $this->attributes->getAttributeObjectByKey('name');
		$idAttribute = $this->attributes->getAttributeObjectByKey('id');
		if(is_object($nameAttribute) && is_object($idAttribute)) {
			$nameAttribute->setReturnValueWithoutPrefix(TRUE);
			$this->attributes->setAttribute('name', $nameAttribute);
			$nameAttributeValue = $nameAttribute->getValueWithoutPrefix();
			$idAttributeValue = $idAttribute->getValue('id');
			if(!empty($nameAttributeValue) && !empty($idAttributeValue)) {
				$this->attributes->setValue('id', $nameAttributeValue);
			}
		}
	}
}
?>