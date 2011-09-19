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
 * Alphabetic rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_Alphabetic extends tx_form_System_Validate_Abstract {

	/**
	 * Allow white space in the submitted value
	 *
	 * @var boolean
	 */
	protected $allowWhiteSpace;

	/**
	 * Alphabetic filter used for validation
	 *
	 * @var tx_form_Filter_Alphabetic
	 */
	protected $filter;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 * @return void
	 */
	public function __construct($arguments = array()) {
		$this->setAllowWhiteSpace($arguments['allowWhiteSpace']);

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
			if ($this->filter === NULL) {
				$className = 'tx_form_System_Filter_Alphabetic';
				$this->filter = t3lib_div::makeInstance($className);
			}

			$this->filter->setAllowWhiteSpace($this->allowWhiteSpace);

			if ($this->filter->filter($value) !== $value) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set TRUE if white space is allowed in submitted value
	 *
	 * @param boolean $allowWhiteSpace TRUE if white space allowed
	 * @return object Rule object
	 */
	public function setAllowWhiteSpace($allowWhiteSpace) {
		if ($allowWhiteSpace === NULL) {
			$this->allowWhiteSpace = FALSE;
		} else {
			$this->allowWhiteSpace = (boolean) $allowWhiteSpace;
		}

		return $this;
	}

	/**
	 * Get the local language label(s) for the message
	 * Overrides the abstract
	 *
	 * @return string The local language message label
	 * @see tx_form_System_Validate_Abstract::_getLocalLanguageLabel()
	 */
	protected function getLocalLanguageLabel() {
		$label = strtolower(get_class($this)) . '.message';
		$messages[] = $this->localizationHandler->getLocalLanguageLabel($label);

		if ($this->allowWhiteSpace) {
			$messages[] = $this->localizationHandler->getLocalLanguageLabel($label . '2');
		}

		$message = implode(', ', $messages);
		return $message;
	}
}
?>