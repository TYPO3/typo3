<?php
namespace TYPO3\CMS\Form\Validation;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Equals rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class EqualsValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_equals';

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
