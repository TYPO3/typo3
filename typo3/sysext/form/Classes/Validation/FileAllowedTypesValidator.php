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
 * File Allowed Types rule
 * The file type must fit one of the given mime types
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FileAllowedTypesValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator implements \TYPO3\CMS\Form\Validation\ValidatorInterface {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_fileallowedtypes';

	/**
	 * The allowed types
	 *
	 * @var array
	 */
	protected $allowedTypes;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setAllowedTypes($arguments['types']);
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
			$fileValue = $this->requestHandler->getByMethod($this->fieldName);
			$value = strtolower($fileValue['type']);
			if (!in_array($value, $this->allowedTypes)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the allowed types
	 *
	 * @param string $allowedTypes Allowed Types
	 * @return object Rule object
	 */
	public function setAllowedTypes($allowedTypes) {
		$allowedTypes = strtolower($allowedTypes);
		$this->allowedTypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(', ', $allowedTypes);
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
		$message = str_replace('%allowedTypes', implode(',', $this->allowedTypes), $message);
		return $message;
	}

}
