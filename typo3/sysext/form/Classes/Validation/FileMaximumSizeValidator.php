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
 * File Maximum size rule
 * The file size must be smaller or equal than the maximum
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FileMaximumSizeValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator implements \TYPO3\CMS\Form\Validation\ValidatorInterface {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_filemaximumsize';

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
			$fileValue = $this->requestHandler->getByMethod($this->fieldName);
			$value = $fileValue['size'];
			if ($value > $this->maximum) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the maximum value
	 *
	 * @param integer $maximum Maximum value
	 * @return object Rule object
	 */
	public function setMaximum($maximum) {
		$this->maximum = (int)$maximum;
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
		$message = str_replace('%maximum', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($this->maximum), $message);
		return $message;
	}

}
