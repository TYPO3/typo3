<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * The container handles single elements.
 *
 * This one is called by FlexFormTabsContainer, FlexFormNoTabsContainer or FlexFormContainerContainer.
 * For single fields, the code is similar to SingleFieldContainer, processing will end up in single
 * element classes depending on specific type of an element. Additionally, it determines if a
 * section is handled and hands over to FlexFormSectionContainer in this case.
 */
class FlexFormElementContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $flexFormFormPrefix = $this->data['flexFormFormPrefix'];
        $parameterArray = $this->data['parameterArray'];

        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        foreach ($flexFormDataStructureArray as $flexFormFieldName => $flexFormFieldArray) {
            if (
                // No item array found at all
                !is_array($flexFormFieldArray)
                // Not a section or container and not a list of single items
                || (!isset($flexFormFieldArray['type']) && !is_array($flexFormFieldArray['config']))
            ) {
                continue;
            }

            if ($flexFormFieldArray['type'] === 'array') {
                // Section
                if (empty($flexFormFieldArray['section'])) {
                    $resultArray['html'] = LF . 'Section expected at ' . $flexFormFieldName . ' but not found';
                    continue;
                }

                $sectionTitle = '';
                if (!empty(trim($flexFormFieldArray['title']))) {
                    $sectionTitle = $languageService->sL(trim($flexFormFieldArray['title']));
                }

                $options = $this->data;
                $options['flexFormDataStructureArray'] = $flexFormFieldArray['el'];
                $options['flexFormRowData'] = isset($flexFormRowData[$flexFormFieldName]['el']) ? $flexFormRowData[$flexFormFieldName]['el'] : [];
                $options['flexFormSectionType'] = $flexFormFieldName;
                $options['flexFormSectionTitle'] = $sectionTitle;
                $options['renderType'] = 'flexFormSectionContainer';
                $sectionContainerResult = $this->nodeFactory->create($options)->render();
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $sectionContainerResult);
            } else {
                // Set up options for single element
                $fakeParameterArray = [
                    'fieldConf' => [
                        'label' => $languageService->sL(trim($flexFormFieldArray['label'])),
                        'config' => $flexFormFieldArray['config'],
                        'children' => $flexFormFieldArray['children'],
                        'defaultExtras' => $flexFormFieldArray['defaultExtras'],
                        'onChange' => $flexFormFieldArray['onChange'],
                    ],
                    'fieldChangeFunc' => $parameterArray['fieldChangeFunc'],
                    'onFocus' => $parameterArray['onFocus'],
                    'label' => $parameterArray['label'],
                ];

                $alertMsgOnChange = '';
                if (
                    $fakeParameterArray['fieldConf']['onChange'] === 'reload'
                    || !empty($GLOBALS['TCA'][$table]['ctrl']['type']) && $GLOBALS['TCA'][$table]['ctrl']['type'] === $flexFormFieldName
                    || !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate']) && GeneralUtility::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'], $flexFormFieldName)
                ) {
                    if ($this->getBackendUserAuthentication()->jsConfirmation(JsConfirmation::TYPE_CHANGE)) {
                        $alertMsgOnChange = 'top.TYPO3.Modal.confirm(TBE_EDITOR.labels.refreshRequired.title, TBE_EDITOR.labels.refreshRequired.content).on("button.clicked", function(e) { if (e.target.name == "ok" && TBE_EDITOR.checkSubmit(-1)) { TBE_EDITOR.submitForm() } top.TYPO3.Modal.dismiss(); });';
                    } else {
                        $alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
                    }
                }
                if ($alertMsgOnChange) {
                    $fakeParameterArray['fieldChangeFunc']['alert'] = $alertMsgOnChange;
                }

                $originalFieldName = $parameterArray['itemFormElName'];
                $fakeParameterArray['itemFormElName'] = $parameterArray['itemFormElName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][vDEF]';
                if ($fakeParameterArray['itemFormElName'] !== $originalFieldName) {
                    // If calculated itemFormElName is different from originalFieldName
                    // change the originalFieldName in TBE_EDITOR_fieldChanged. This is
                    // especially relevant for wizards writing their content back to hidden fields
                    if (!empty($fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'])) {
                        $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = str_replace($originalFieldName, $fakeParameterArray['itemFormElName'], $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged']);
                    }
                }
                $fakeParameterArray['itemFormElID'] = $fakeParameterArray['itemFormElName'];
                if (isset($flexFormRowData[$flexFormFieldName]['vDEF'])) {
                    $fakeParameterArray['itemFormElValue'] = $flexFormRowData[$flexFormFieldName]['vDEF'];
                } else {
                    $fakeParameterArray['itemFormElValue'] = $fakeParameterArray['fieldConf']['config']['default'];
                }

                $options = $this->data;
                $options['parameterArray'] = $fakeParameterArray;
                $options['elementBaseName'] = $this->data['elementBaseName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][vDEF]';

                if (!empty($flexFormFieldArray['config']['renderType'])) {
                    $options['renderType'] = $flexFormFieldArray['config']['renderType'];
                } else {
                    // Fallback to type if no renderType is given
                    $options['renderType'] = $flexFormFieldArray['config']['type'];
                }
                $childResult = $this->nodeFactory->create($options)->render();

                // Possible line breaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace (?!)
                $processedTitle = str_replace('\\n', '<br />', htmlspecialchars($fakeParameterArray['fieldConf']['label']));
                $html = [];
                $html[] = '<div class="form-section">';
                $html[] =    '<div class="form-group t3js-formengine-palette-field t3js-formengine-validation-marker">';
                $html[] =        '<label class="t3js-formengine-label">';
                $html[] =            BackendUtility::wrapInHelp($parameterArray['_cshKey'], $flexFormFieldName, $processedTitle);
                $html[] =        '</label>';
                $html[] =        '<div class="t3js-formengine-field-item">';
                $html[] =            $childResult['html'];
                $html[] =        '</div>';
                $html[] =    '</div>';
                $html[] = '</div>';

                $resultArray['html'] .= implode(LF, $html);
                $childResult['html'] = '';
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult);
            }
        }

        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
