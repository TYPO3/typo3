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
 * In array rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_Inarray extends tx_form_System_Validate_Abstract {

	/**
	 * Haystack to search in
	 *
	 * @var array
	 */
	protected $array;

	/**
	 * Search strict
	 *
	 * @var boolean
	 */
	protected $strict;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 * @return void
	 */
	public function __construct($arguments) {
		$this->setArray($arguments['array.'])
			->setStrict($arguments['strict']);

		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see tx_form_System_Validate_Interface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);

			if (!in_array($value, $this->array, $this->strict)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the array as haystack
	 *
	 * @param array $array The haystack
	 * @return object Rule object
	 */
	public function setArray($array) {
		$this->array = (array) $array;

		return $this;
	}

	/**
	 * Set the strict mode for the search
	 *
	 * @param boolean $strict True if strict
	 * @return object Rule object
	 */
	public function setStrict($strict) {
		$this->strict = (boolean) $strict;

		return $this;
	}
}
?>