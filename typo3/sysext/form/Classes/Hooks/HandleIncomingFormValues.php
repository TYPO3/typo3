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
use TYPO3\CMS\Form\Domain\Builder\FormBuilder;
use TYPO3\CMS\Form\Domain\Model\Element;
use TYPO3\CMS\Form\Domain\Model\ValidationElement;

/**
 * Handle the incoming form data
 */
class HandleIncomingFormValues implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Form\Utility\SessionUtility
     */
    protected $sessionUtility;

    /**
     * @param \TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility
     * @return void
     */
    public function injectSessionUtility(\TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility)
    {
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
    public function handleIncomingFormValues(Element $element, ValidationElement $validationElement, $modelValue, FormBuilder $formBuilder)
    {
        $elementName = $element->getName();

        if ($element->getElementType() === 'CHECKBOX') {
            $groupedElement = false;
            if ($element->getParentElement()->getElementType() === 'CHECKBOXGROUP') {
                $incomingName = $element->getParentElement()->getName();
                $groupedElement = true;
            } else {
                $incomingName = $elementName;
            }
            $incomingData = $formBuilder->getIncomingData()->getIncomingField($incomingName);
            $checked = false;
            if (is_array($incomingData)) {
                if (
                    isset($incomingData[$elementName])
                    && $incomingData[$elementName] !== ''
                ) {
                    $this->setAttribute($element, 'checked', 'checked');
                    $checked = true;
                } else {
                    $this->setAttribute($element, 'checked', null);
                }
            } else {
                if (
                    (!empty($modelValue) && $incomingData === $modelValue)
                    || $incomingData === $incomingName . '-' . $element->getElementCounter()
                ) {
                    $this->setAttribute($element, 'checked', 'checked');
                    $checked = true;
                } else {
                    $this->setAttribute($element, 'checked', null);
                }
            }
            if (
                $groupedElement
                && $checked
            ) {
                $element->getParentElement()->setAdditionalArgument('atLeastOneCheckedChildElement', true);
            }
        } elseif ($element->getElementType() === 'RADIO') {
            $groupedElement = false;
            if ($element->getParentElement()->getElementType() === 'RADIOGROUP') {
                $incomingName = $element->getParentElement()->getName();
                $groupedElement = true;
            } else {
                $incomingName = $elementName;
            }
            $checked = false;
            $incomingData = $formBuilder->getIncomingData()->getIncomingField($incomingName);
            if (
                (!empty($modelValue) && $incomingData === $modelValue)
                || $incomingData === $incomingName . '-' . $element->getElementCounter()
            ) {
                $this->setAttribute($element, 'checked', 'checked');
                $checked = true;
            } else {
                $this->setAttribute($element, 'checked', null);
            }
            if (
                $groupedElement
                && $checked
            ) {
                $element->getParentElement()->setAdditionalArgument('atLeastOneCheckedChildElement', true);
            }
        } elseif ($element->getElementType() === 'OPTION') {
            $modelValue = (string)($element->getAdditionalArgument('value') ?: $element->getElementCounter());
            if ($element->getParentElement()->getElementType() === 'OPTGROUP') {
                $parentName = $element->getParentElement()->getParentElement()->getName();
            } else {
                $parentName = $element->getParentElement()->getName();
            }
            $incomingData = $formBuilder->getIncomingData()->getIncomingField($parentName);

            /* Multiselect */
            if (is_array($incomingData)) {
                if (in_array($modelValue, $incomingData, true)) {
                    $element->setAdditionalArgument('selected', 'selected');
                } else {
                    $element->setAdditionalArgument('selected', null);
                }
            } else {
                if ($modelValue === $incomingData) {
                    $element->setAdditionalArgument('selected', 'selected');
                } else {
                    $element->setAdditionalArgument('selected', null);
                }
            }
        } elseif ($element->getElementType() === 'TEXTAREA') {
            $incomingData = $formBuilder->getIncomingData()->getIncomingField($elementName);
            $element->setAdditionalArgument('text', $incomingData);
        } elseif ($element->getElementType() === 'FILEUPLOAD') {
            if (
                $formBuilder->getValidationErrors() == null
                || (
                    $formBuilder->getValidationErrors()
                    && $formBuilder->getValidationErrors()->forProperty($elementName)->hasErrors() !== true
                )
            ) {
                $uploadedFiles = $formBuilder->getIncomingData()->getIncomingField($elementName);
                if (is_array($uploadedFiles)) {
                    foreach ($uploadedFiles as $key => &$file) {
                        $tempFilename = $this->saveUploadedFile($file['tmp_name']);
                        if (!$tempFilename) {
                            unset($uploadedFiles[$key]);
                            continue;
                        }
                        $file['tempFilename'] = $tempFilename;
                    }
                    $element->setAdditionalArgument('uploadedFiles', $uploadedFiles);
                    $this->setAttribute($element, 'value', '');
                    $this->sessionUtility->setSessionData($elementName, $uploadedFiles);
                }
            }
        }
    }

    /**
     * Save a uploaded file
     *
     * @param string $uploadedFile
     * @return NULL|string
     */
    public function saveUploadedFile($uploadedFile)
    {
        if (is_uploaded_file($uploadedFile)) {
            $tempFilename = GeneralUtility::upload_to_tempfile($uploadedFile);
            if (TYPO3_OS === 'WIN') {
                $tempFilename = GeneralUtility::fixWindowsFilePath($tempFilename);
            }
            if ($tempFilename !== '') {
                return $tempFilename;
            }
        }
        return null;
    }

    /**
     * Set the value Attribute to the right place
     *
     * @param Element $element The element
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setAttribute(Element $element, $key, $value = '')
    {
        if ($element->getHtmlAttribute($key) !== null) {
            $element->setHtmlAttribute($key, $value);
        } else {
            $element->setAdditionalArgument($key, $value);
        }
    }
}
