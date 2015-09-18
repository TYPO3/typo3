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
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to result.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function isValid($value) {
		// @todo $value is never used, what's the process flow here?

		$allowedTypes = strtolower($this->options['types']);
		$this->options['types'] = GeneralUtility::trimExplode(', ', $allowedTypes);

		if (isset($this->rawArgument[$this->options['element']]['name'])) {
			$request = $this->rawArgument[$this->options['element']];
			$this->checkFileType($request);
		} else {
				// multi upload
			foreach ($this->rawArgument[$this->options['element']] as $file) {
				if (
					$file['name'] === ''
					&& $file['type'] === ''
					&& $file['tmp_name'] === ''
					&& $file['size'] === 0
				) {
					continue;
				}
				$this->checkFileType($file);
			}
		}
	}

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to result.
	 *
	 * @param array $request
	 * @return void
	 */
	public function checkFileType($request) {
		// @todo Using $_FILES[...]['type] is probably insecure, since it's submitted by the client directly
		$value = strtolower($request['type']);
		if (!in_array($value, $this->options['types'])) {
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
