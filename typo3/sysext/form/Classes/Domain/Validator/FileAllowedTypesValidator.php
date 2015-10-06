<?php
namespace TYPO3\CMS\Form\Domain\Validator;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileAllowedTypesValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'element' => array('', 'The name of the element', 'string', TRUE),
		'errorMessage' => array('', 'The error message', 'array', TRUE),
		'types' => array('', 'The allowed file types', 'string', TRUE),
	);

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_fileallowedtypes';

	/**
	 * Check if the file mime type is allowed.
	 *
	 * The mime type is set in the propertymapper
	 * @see TYPO3\CMS\Form\Domain\Property\TypeConverter::convertFrom
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function isValid($value) {
		$allowedTypes = strtolower($this->options['types']);
		$allowedMimeTypes = GeneralUtility::trimExplode(', ', $allowedTypes);
		$fileMimeType = strtolower($value['type']);
		if (!in_array($fileMimeType, $allowedMimeTypes, TRUE)) {
			$this->addError(
				$this->renderMessage(
					$this->options['errorMessage'][0],
					$this->options['errorMessage'][1],
					'error'
				),
				1442006702
			);
		}
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	public function substituteMarkers($message) {
		$message = str_replace('%allowedTypes', implode(',', $this->options['types']), $message);
		return $message;
	}
}
