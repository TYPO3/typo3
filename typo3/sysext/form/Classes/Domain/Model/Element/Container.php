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
 * Class for the form container elements
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_element_container extends tx_form_domain_model_element_abstract {

	/**
	 * Child elements of this object
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Add child object to this element
	 *
	 * @param object $element The child object
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function addElement($element) {
		$this->elements[] = $element;
		return $this;
	}

	/**
	 * Get the child elements
	 *
	 * @return array Child objects
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getElements() {
		return $this->elements;
	}
}
?>