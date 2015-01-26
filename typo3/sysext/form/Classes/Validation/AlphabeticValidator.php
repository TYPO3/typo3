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
 * Alphabetic rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AlphabeticValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_alphabetic';

	/**
	 * Allow white space in the submitted value
	 *
	 * @var boolean
	 */
	protected $allowWhiteSpace;

	/**
	 * Alphabetic filter used for validation
	 *
	 * @var \TYPO3\CMS\Form\Filter\AlphabeticFilter
	 */
	protected $filter;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments = array()) {
		$this->setAllowWhiteSpace($arguments['allowWhiteSpace']);
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
			if ($this->filter === NULL) {
				$className = 'TYPO3\\CMS\\Form\\Filter\\AlphabeticFilter';
				$this->filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
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
			$this->allowWhiteSpace = (bool) $allowWhiteSpace;
		}
		return $this;
	}

	/**
	 * Get the local language label(s) for the message
	 * Overrides the abstract
	 *
	 * @return string The local language message label
	 * @see \TYPO3\CMS\Form\Validation\AbstractValidator::_getLocalLanguageLabel()
	 */
	protected function getLocalLanguageLabel() {
		$label = static::LOCALISATION_OBJECT_NAME . '.message';
		$messages[] = $this->localizationHandler->getLocalLanguageLabel($label);
		if ($this->allowWhiteSpace) {
			$messages[] = $this->localizationHandler->getLocalLanguageLabel($label . '2');
		}
		$message = implode(', ', $messages);
		return $message;
	}

}
