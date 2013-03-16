<?php
namespace TYPO3\CMS\Form\Domain\Model;

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
 * A form
 *
 * Takes the incoming Typoscipt and adds all the necessary form objects
 * according to the configuration.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class Form extends \TYPO3\CMS\Form\Domain\Model\Element\ContainerElement {

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
		'enctype' => 'multipart/form-data',
		'id' => '',
		'lang' => '',
		'method' => 'post',
		'name' => '',
		'style' => '',
		'title' => ''
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
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return object
	 */
	public function setAttribute($attribute, $value) {
		if (array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}
		if ($attribute == 'id' || $attribute == 'name') {
			$this->equalizeNameAndIdAttribute();
		}
		return $this;
	}

	/**
	 * Makes the value of attributes 'name' and 'id' equal
	 * when both have been filled.
	 *
	 * @return void
	 */
	protected function equalizeNameAndIdAttribute() {
		/** @var $nameAttribute \TYPO3\CMS\Form\Domain\Model\Attribute\NameAttribute */
		$nameAttribute = $this->attributes->getAttributeObjectByKey('name');
		$idAttribute = $this->attributes->getAttributeObjectByKey('id');
		if (is_object($nameAttribute) && is_object($idAttribute)) {
			$nameAttribute->setReturnValueWithoutPrefix(TRUE);
			$this->attributes->setAttribute('name', $nameAttribute);
			$nameAttributeValue = $nameAttribute->getValueWithoutPrefix();
			$idAttributeValue = $idAttribute->getValue('id');
			if (!empty($nameAttributeValue) && !empty($idAttributeValue)) {
				$this->attributes->setValue('id', $nameAttributeValue);
			}
		}
	}

}

?>