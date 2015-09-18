<?php
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Element;
use TYPO3\CMS\Form\Domain\Model\ValidationElement;
use TYPO3\CMS\Form\Domain\Builder\FormBuilder;

/**
 * Handle the incoming form data
 */
class HandleIncomingFormValues implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Form\Utility\SessionUtility
	 */
	protected $sessionUtility;

	/**
	 * @param \TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility
	 * @return void
	 */
	public function injectSessionUtility(\TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility) {
		$this->sessionUtility = $sessionUtility;
	}

	/**
	 * Handle the incoming form data
	 *
	 * @param Element $element The element
	 * @param ValidationElement $validationElement
	 * @param mixed $modelValue
	 * @param FormBuilder $formBuilder
	 * @return void
	 */
	public function handleIncomingFormValues(Element $element, ValidationElement $validationElement, $modelValue, FormBuilder $formBuilder) {
		$elementName = $element->getName();

		if ($element->getElementType() === 'CHECKBOX') {
			$groupedElement = FALSE;
			if ($element->getParentElement()->getElementType() === 'CHECKBOXGROUP') {
				$incomingName = $element->getParentElement()->getName();
				$groupedElement = TRUE;
			} else {
				$incomingName = $elementName;
			}
			$incomingData = $formBuilder->getIncomingData()->getIncomingField($incomingName);
			$checked = FALSE;
			if (is_array($incomingData)) {
				if (
					isset($incomingData[$elementName])
					&& $incomingData[$elementName] !== ''
				) {
					$this->setAttribute($element, 'checked', 'checked');
					$checked = TRUE;
				} else {
					$this->setAttribute($element, 'checked', NULL);
				}
			} else {
				if ($incomingData === $modelValue) {
					$this->setAttribute($element, 'checked', 'checked');
					$checked = TRUE;
				} else {
					$this->setAttribute($element, 'checked', NULL);
				}
			}
			if (
				$groupedElement
				&& $checked
			) {
				$element->getParentElement()->setAdditionalArgument('atLeastOneCheckedChildElement', TRUE);
			}
		} elseif ($element->getElementType() === 'RADIO') {
			$groupedElement = FALSE;
			if ($element->getParentElement()->getElementType() === 'RADIOGROUP') {
				$incomingName = $element->getParentElement()->getName();
				$groupedElement = TRUE;
			} else {
				$incomingName = $elementName;
			}
			$checked = FALSE;
			$incomingData = $formBuilder->getIncomingData()->getIncomingField($incomingName);
			if ($incomingData === $modelValue) {
				$this->setAttribute($element, 'checked', 'checked');
				$checked = TRUE;
			} else {
				$this->setAttribute($element, 'checked', NULL);
			}
			if (
				$groupedElement
				&& $checked
			) {
				$element->getParentElement()->setAdditionalArgument('atLeastOneCheckedChildElement', TRUE);
			}
		} elseif ($element->getElementType() === 'OPTION') {
			if ($element->getParentElement()->getElementType() === 'OPTGROUP') {
				$parentName = $element->getParentElement()->getParentElement()->getName();
			} else {
				$parentName = $element->getParentElement()->getName();
			}
			$incomingData = $formBuilder->getIncomingData()->getIncomingField($parentName);
			/* Multiselect */
			if (is_array($incomingData)) {
				if (in_array($modelValue, $incomingData)) {
					$element->setHtmlAttribute('selected', 'selected');
				} else {
					$element->setHtmlAttribute('selected', NULL);
				}
			} else {
				if ($modelValue === $incomingData) {
					$element->setHtmlAttribute('selected', 'selected');
				} else {
					$element->setHtmlAttribute('selected', NULL);
				}
			}
		} elseif ($element->getElementType() === 'FILEUPLOAD') {
			if (
				$formBuilder->getValidationErrors() == NULL
				|| (
					$formBuilder->getValidationErrors()
					&& $formBuilder->getValidationErrors()->forProperty($elementName)->hasErrors() !== TRUE
				)
			) {
				$formPrefix = $formBuilder->getFormPrefix();
				if (
					isset($_FILES['tx_form_form']['tmp_name'][$formPrefix])
					&& is_array($_FILES['tx_form_form']['tmp_name'][$formPrefix])
				) {
					foreach ($_FILES['tx_form_form']['tmp_name'][$formPrefix] as $fieldName => $uploadedFile) {
						$uploadedFiles = array();
						if (is_string($uploadedFile)) {
							$uploadedFiles[] = $this->saveUploadedFile($formPrefix, $fieldName, -1, $uploadedFile);
						} else {
								// multi upload
							foreach ($uploadedFile as $key => $file) {
								$uploadedFiles[] = $this->saveUploadedFile($formPrefix, $fieldName, $key, $file);
							}
						}
						$element->setAdditionalArgument('uploadedFiles', $uploadedFiles);
						$this->setAttribute($element, 'value', '');
						$this->sessionUtility->setSessionData($fieldName, $uploadedFiles);
					}
				}
			}
		}
	}

	/**
	 * Save a uploaded file
	 *
	 * @param string $formPrefix
	 * @param string $fieldName
	 * @param integer $key
	 * @param string $uploadedFile
	 * @return NULL|array
	 */
	public function saveUploadedFile($formPrefix, $fieldName, $key, $uploadedFile) {
		if (is_uploaded_file($uploadedFile)) {
			$tempFilename = GeneralUtility::upload_to_tempfile($uploadedFile);
			if (TYPO3_OS === 'WIN') {
				$tempFilename = GeneralUtility::fixWindowsFilePath($tempFilename);
			}
			if ($tempFilename !== '') {
				if ($key == -1) {
					$originalFilename = $_FILES['tx_form_form']['name'][$formPrefix][$fieldName];
					$size = $_FILES['tx_form_form']['size'][$formPrefix][$fieldName];
				} else {
					$originalFilename = $_FILES['tx_form_form']['name'][$formPrefix][$fieldName][$key];
					$size = $_FILES['tx_form_form']['size'][$formPrefix][$fieldName][$key];
				}
				$fileInfo = GeneralUtility::makeInstance(FileInfo::class, $tempFilename);
				return array(
						'tempFilename' => $tempFilename,
						'originalFilename' => $originalFilename,
						'type' => $fileInfo->getMimeType(),
						'size' => (int)$size
					);
			}
		}
		return NULL;
	}

	/**
	 * Set the value Attribute to the right place
	 *
	 * @param Element $element The element
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function setAttribute(Element $element, $key, $value = '') {
		if ($element->getHtmlAttribute($key) !== NULL) {
			$element->setHtmlAttribute($key, $value);
		} else {
			$element->setAdditionalArgument($key, $value);
		}
	}

}
