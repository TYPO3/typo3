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

use TYPO3\CMS\Form\Domain\Model\Configuration;

abstract class AbstractValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate';

	/**
	 * @var \TYPO3\CMS\Form\Utility\FormUtility
	 */
	protected $formUtility;

	/**
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * @var mixed
	 */
	protected $rawArgument;

	/**
	 * @param \TYPO3\CMS\Form\Utility\FormUtility $formUtility
	 * @return void
	 */
	public function injectFormUtility(\TYPO3\CMS\Form\Utility\FormUtility $formUtility) {
		$this->formUtility = $formUtility;
	}

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'element' => array('', 'The name of the element', 'string', TRUE),
		'errorMessage' => array('', 'The error message', 'array', TRUE),
	);

	/**
	 * This validator always needs to be executed even if the given value is empty.
	 * See AbstractValidator::validate()
	 *
	 * @var boolean
	 */
	protected $acceptsEmptyValues = FALSE;

	/**
	 * @param Configuration $configuration
	 */
	public function setConfiguration(Configuration $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @param mixed $rawArgument
	 */
	public function setRawArgument($rawArgument) {
		$this->rawArgument = $rawArgument;
	}

	/**
	 * Substitute makers in the message text
	 * In some cases this method will be override by rule class
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	public function substituteMarkers($message) {
		return $message;
	}

	/**
	 * Get the local language label(s) for the message
	 * In some cases this method will be override by rule class
	 *
	 * @param string $type The type
	 * @return string The local language message label
	 */
	public function getLocalLanguageLabel($type = '') {
		$label = static::LOCALISATION_OBJECT_NAME . '.' . $type;
		$message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label, 'form');
		return $message;
	}

	/**
	 * Set the message, like 'required' for the validation rule
	 * and substitutes markers for values, like %maximum
	 *
	 * The output will be a rendered cObject if allowed.
	 * If cObject rendering is allowed:
	 * If no parameter is given, it will take the default locallang label
	 * If only first parameter, then it's supposed to be a TEXT cObj
	 * When both are filled, it's supposed to be a cObj made by the administrator
	 * In the last case, no markers will be substituted
	 * If cObject rendering is not allowed:
	 * If no parameter is given, it will take the default locallang label
	 * If the first parameter is given and its no array, then the markers
	 * are substituted.
	 * If the first parameter is given and its a array, then we try some fallbacks:
	 * If the array contains a value key, then return the value
	 * If the data array contains a data key, then try to localize it via
	 * extbase LocalizationUtility::translate
	 *
	 * @param string|array $message Message as string or TS
	 * @param string $type Name of the cObj
	 * @param string $messageType message or error
	 * @param \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator $validator
	 * @return string
	 */
	public function renderMessage($message = '', $type = 'TEXT', $messageType = 'message') {
		if ($this->configuration->getContentElementRendering()) {
			if (empty($message)) {
				if (!empty($type)) {
						// cObj Text, cast to - message = "some message", type = "TEXT"
					$message = $type;
					$type = 'TEXT';
				} else {
						// cObj Text, default locallang label
					$type = 'TEXT';
					$message = $this->getLocalLanguageLabel($messageType);
				}
				$value['value'] = $this->substituteMarkers($message);
			} elseif (!is_array($message)) {
					// cObj Text, $message is string and replaced by the validator function
				$value['value'] = $this->substituteMarkers($message);
			} else {
					// cObj $type, message is rendered as cOnj
				$value = $message;
			}
			$message = $this->formUtility->renderContentObject($type, $value);
		} else {
			if (empty($message)) {
				if (!empty($type)) {
					$message = $type;
				} else {
					$message = $this->getLocalLanguageLabel($messageType);
				}
				$message = $this->substituteMarkers($message);
			} elseif (!is_array($message)) {
				$message = $this->substituteMarkers($message);
			} else {
				if (isset($message['value'])) {
					$message = $message['value'];
				} elseif (isset($message['data'])) {
					$message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($message['data'], 'form');
				} else {
					$message = '';
				}
			}
		}
		return $message;
	}
}
