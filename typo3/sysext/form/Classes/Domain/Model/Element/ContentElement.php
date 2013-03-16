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
 * Content model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ContentElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * @var string
	 */
	protected $elementType = self::ELEMENT_TYPE_CONTENT;

	/**
	 * @var string
	 */
	protected $objectName;

	/**
	 * @var array
	 */
	protected $objectConfiguration;

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array();

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array();

	/**
	 * Set the content for the element
	 *
	 * @param string $objectName The name of the object
	 * @param array $objectConfiguration The configuration of the object
	 * @return void
	 */
	public function setData($objectName, array $objectConfiguration) {
		$this->objectName = $objectName;
		$this->objectConfiguration = $objectConfiguration;
	}

	/**
	 * Return the value data of the content object
	 * Calls tslib_cObj->cObjGetSingle which renders
	 * configuration into html string
	 *
	 * @return string
	 */
	public function getData() {
		$data = $this->localCobj->cObjGetSingle($this->objectName, $this->objectConfiguration);
		return $data;
	}

}

?>