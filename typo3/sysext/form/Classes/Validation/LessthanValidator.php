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
 * Less than rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class LessthanValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_lessthan';

	/**
	 * Maximum value
	 *
	 * @var mixed
	 */
	protected $maximum;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setMaximum($arguments['maximum']);
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
			$value = $this->requestHandler->getByMethod($this->fieldName);
			if ($value >= $this->maximum || !is_numeric($value)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the maximum value
	 *
	 * @param mixed $maximum Maximum value
	 * @return object Rule object
	 */
	public function setMaximum($maximum) {
		$this->maximum = $maximum;
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
		$message = str_replace('%maximum', $this->maximum, $message);
		return $message;
	}

}
