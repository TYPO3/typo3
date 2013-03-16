<?php
namespace TYPO3\CMS\Form\Validation;

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
 * Equals rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class EqualsValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Field to compare with value
	 *
	 * @var string
	 */
	protected $field;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setField($arguments['field']);
		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			if (!$this->requestHandler->has($this->field)) {
				return FALSE;
			} else {
				$value = $this->requestHandler->getByMethod($this->fieldName);
				$comparisonValue = $this->requestHandler->getByMethod($this->field);
				if ($value !== $comparisonValue) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Set the field to compare value with
	 *
	 * @param string $field Field to compare
	 * @return Rule object
	 */
	public function setField($field) {
		$this->field = (string) $field;
		return $this;
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	protected function substituteValues($message) {
		$message = str_replace('%field', $this->field, $message);
		return $message;
	}

}

?>