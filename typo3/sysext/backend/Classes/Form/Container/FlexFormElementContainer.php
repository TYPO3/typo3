<?php

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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Backend\Form\Behavior\ReloadOnFieldChange;
use TYPO3\CMS\Backend\Form\Behavior\UpdateValueOnFieldChange;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;

/**
 * The container handles single elements.
 *
 * This one is called by FlexFormTabsContainer, FlexFormNoTabsContainer or FlexFormContainerContainer.
 * For single fields, the code is similar to SingleFieldContainer, processing will end up in single
 * element classes depending on specific renderType of an element. Additionally, it determines if a
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
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $flexFormFormPrefix = $this->data['flexFormFormPrefix'];
        $parameterArray = $this->data['parameterArray'];

        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $showFieldName = $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] && $this->getBackendUserAuthentication()->isAdmin();

        foreach ($flexFormDataStructureArray as $flexFormFieldName => $flexFormFieldArray) {
            if (
                // No item array found at all
                !is_array($flexFormFieldArray)
                // Not a section or container and not a list of single items
                || (!isset($flexFormFieldArray['type']) && !is_array($flexFormFieldArray['config']))
            ) {
                continue;
            }

            if (($flexFormFieldArray['type'] ?? null) === 'array') {
                // Section
                if (empty($flexFormFieldArray['section'])) {
                    $resultArray['html'] = LF . 'Section expected at ' . $flexFormFieldName . ' but not found';
                    continue;
                }

                $options = $this->data;
                $options['flexFormDataStructureArray'] = $flexFormFieldArray;
                $options['flexFormRowData'] = $flexFormRowData[$flexFormFieldName]['el'] ?? [];
                $options['flexFormFieldName'] = $flexFormFieldName;
                $options['renderType'] = 'flexFormSectionContainer';
                $sectionContainerResult = $this->nodeFactory->create($options)->render();
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $sectionContainerResult);
            } else {
                // Set up options for single element
                $fakeParameterArray = [
                    'fieldConf' => [
                        'label' => $languageService->sL(trim($flexFormFieldArray['label'] ?? '')),
                        'config' => $flexFormFieldArray['config'] ?? [],
                        'children' => $flexFormFieldArray['children'] ?? [],
                        // https://docs.typo3.org/m/typo3/reference-tca/master/en-us/Columns/Properties/OnChange.html
                        'onChange' => $flexFormFieldArray['onChange'] ?? '',
                    ],
                    'fieldChangeFunc' => $parameterArray['fieldChangeFunc'],
                    'label' => $parameterArray['label'] ?? '',
                ];

                if (isset($flexFormFieldArray['description']) && !empty($flexFormFieldArray['description'])) {
                    $fakeParameterArray['fieldConf']['description'] = $flexFormFieldArray['description'];
                }

                if (isset($fakeParameterArray['fieldConf']['onChange']) && $fakeParameterArray['fieldConf']['onChange'] === 'reload') {
                    $confirmation = $this->getBackendUserAuthentication()->jsConfirmation(JsConfirmation::TYPE_CHANGE);
                    $fakeParameterArray['fieldChangeFunc']['alert'] = new ReloadOnFieldChange($confirmation);
                }

                $originalFieldName = $parameterArray['itemFormElName'];
                $fakeParameterArray['itemFormElName'] = $parameterArray['itemFormElName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][vDEF]';
                if ($fakeParameterArray['itemFormElName'] !== $originalFieldName) {
                    // If calculated itemFormElName is different from originalFieldName
                    // change the originalFieldName in TBE_EDITOR_fieldChanged. This is
                    // especially relevant for wizards writing their content back to hidden fields
                    $onFieldChange = $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] ?? null;
                    if ($onFieldChange instanceof UpdateValueOnFieldChange) {
                        $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = $onFieldChange->withElementName($fakeParameterArray['itemFormElName']);
                    } elseif (!empty($onFieldChange)) {
                        // @deprecated
                        $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = str_replace($originalFieldName, $fakeParameterArray['itemFormElName'], $onFieldChange);
                    }
                }
                $fakeParameterArray['itemFormElID'] = $parameterArray['itemFormElID'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $flexFormFieldName) . '_' . md5($fakeParameterArray['itemFormElName']);
                if (isset($flexFormRowData[$flexFormFieldName]['vDEF'])) {
                    $fakeParameterArray['itemFormElValue'] = $flexFormRowData[$flexFormFieldName]['vDEF'];
                } else {
                    $fakeParameterArray['itemFormElValue'] = $fakeParameterArray['fieldConf']['config']['default'];
                }

                $options = $this->data;
                // Set either flexFormFieldName or flexFormContainerFieldName, depending on if we are a "regular" field or a flex container section field
                if (empty($options['flexFormFieldName'])) {
                    $options['flexFormFieldName'] = $flexFormFieldName;
                } else {
                    $options['flexFormContainerFieldName'] = $flexFormFieldName;
                }
                $options['parameterArray'] = $fakeParameterArray;
                $options['elementBaseName'] = $this->data['elementBaseName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][vDEF]';

                if (!empty($flexFormFieldArray['config']['renderType'])) {
                    $options['renderType'] = $flexFormFieldArray['config']['renderType'];
                } else {
                    // Fallback to type if no renderType is given
                    $options['renderType'] = $flexFormFieldArray['config']['type'];
                }
                $childResult = $this->nodeFactory->create($options)->render();

                if (!empty($childResult['html'])) {
                    // Possible line breaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace (?!)
                    $processedTitle = str_replace('\\n', '<br />', htmlspecialchars($fakeParameterArray['fieldConf']['label']));
                    $html = [];
                    $html[] = '<div class="form-section">';
                    $html[] =   '<div class="form-group t3js-formengine-palette-field t3js-formengine-validation-marker">';
                    $html[] =       '<label class="t3js-formengine-label">';
                    $html[] =           BackendUtility::wrapInHelp($parameterArray['_cshKey'], $flexFormFieldName, $processedTitle);
                    $html[] =           $showFieldName ? ('<code>[' . htmlspecialchars($flexFormFieldName) . ']</code>') : '';
                    $html[] =       '</label>';
                    $html[] =       '<div class="formengine-field-item t3js-formengine-field-item">';
                    $html[] =           $childResult['html'];
                    $html[] =       '</div>';
                    $html[] =   '</div>';
                    $html[] = '</div>';
                    $resultArray['html'] .= implode(LF, $html);
                    $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, false);
                }
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
