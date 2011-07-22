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
 * Content model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_element_content extends tx_form_domain_model_element_abstract {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array();

	/**
	 * Mandatory attributes for this object
	 * @var array
	 */
	protected $mandatoryAttributes = array();

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
	 * Set the content for the element
	 *
	 * @param string $cObj The name of the object
	 * @param array $cObjDot The configuration of the object
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setData($cObj, $cObjDot) {
		$this->cObj = $cObj;
		$this->cObjDot = $cObjDot;
	}

	/**
	 * Return the value data of the content object
	 * Calls tslib_cObj->cObjGetSingle which renders
	 * configuration into html string
	 *
	 * @return string
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getData() {
		$data = $this->localCobj->cObjGetSingle($this->cObj, $this->cObjDot);

		return $data;
	}
}
?>