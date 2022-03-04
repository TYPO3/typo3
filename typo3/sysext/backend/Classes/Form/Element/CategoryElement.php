<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render the category element (a category "select" tree).
 */
class CategoryElement extends AbstractFormElement
{
    private const MIN_ITEMS_COUNT = 5;
    private const DEFAULT_ITEMS_COUNT = 15;
    private const ITEM_HEIGHT_BASE = 20;

    /**
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
    ];

    /**
     * Render the category tree
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $fieldName = $this->data['fieldName'];
        $tableName = $this->data['tableName'];
        $parameterArray = $this->data['parameterArray'];
        $formElementId = md5($parameterArray['itemFormElName']);

        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];
        $readOnly = (bool)($config['readOnly'] ?? false);
        $expanded = (bool)($config['treeConfig']['appearance']['expandAll'] ?? false);
        $showHeader = (bool)($config['treeConfig']['appearance']['showHeader'] ?? false);
        $exclusiveKeys = $config['exclusiveKeys'] ?? '';
        $height = ((int)($config['size'] ?? 0) > 0)
            ? max(self::MIN_ITEMS_COUNT, (int)$config['size'])
            : self::DEFAULT_ITEMS_COUNT;
        $heightInPx = $height * self::ITEM_HEIGHT_BASE;
        $treeWrapperId = 'tree_' . $formElementId;
        $fieldId = 'tree_record_' . $formElementId;

        $dataStructureIdentifier = '';
        $flexFormSheetName = '';
        $flexFormFieldName = '';
        $flexFormContainerName = '';
        $flexFormContainerIdentifier = '';
        $flexFormContainerFieldName = '';
        $flexFormSectionContainerIsNew = false;
        if ($this->data['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            $dataStructureIdentifier = $this->data['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'];
            if (isset($this->data['flexFormSheetName'])) {
                $flexFormSheetName = $this->data['flexFormSheetName'];
            }
            if (isset($this->data['flexFormFieldName'])) {
                $flexFormFieldName = $this->data['flexFormFieldName'];
            }
            if (isset($this->data['flexFormContainerName'])) {
                $flexFormContainerName = $this->data['flexFormContainerName'];
            }
            if (isset($this->data['flexFormContainerFieldName'])) {
                $flexFormContainerFieldName = $this->data['flexFormContainerFieldName'];
            }
            if (isset($this->data['flexFormContainerIdentifier'])) {
                $flexFormContainerIdentifier = $this->data['flexFormContainerIdentifier'];
            }
            // Add a flag this is a tree in a new flex section container element. This is needed to initialize
            // the databaseRow with this container again so the tree data provider is able to calculate tree items.
            if (!empty($this->data['flexSectionContainerPreparation'])) {
                $flexFormSectionContainerIsNew = true;
            }
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        if (!$readOnly && !empty($fieldWizardHtml)) {
            $fieldWizardHtml = '<div class="form-wizards-items-bottom">' . $fieldWizardHtml . '</div>';
        }

        $recordElementAttributes = [
            'id' => $fieldId,
            'type' => 'hidden',
            'class' => 'treeRecord',
            'name' => $parameterArray['itemFormElName'],
            'value' => implode(',', $parameterArray['itemFormElValue']),
            'data-uid' => (int)$this->data['vanillaUid'],
            'data-command' => $this->data['command'],
            'data-fieldname' => $fieldName,
            'data-tablename' => $tableName,
            'data-read-only' => $readOnly,
            'data-tree-show-toolbar' => $showHeader,
            'data-recordtypevalue' => $this->data['recordTypeValue'],
            'data-relatedfieldname' => $parameterArray['itemFormElName'],
            'data-flexformsheetname' => $flexFormSheetName,
            'data-flexformfieldname' => $flexFormFieldName,
            'data-tree-exclusive-keys' => $exclusiveKeys,
            'data-flexformcontainername' => $flexFormContainerName,
            'data-datastructureidentifier' => $dataStructureIdentifier,
            'data-tree-expand-up-to-level' => $expanded ? '999' : '1',
            'data-flexformcontainerfieldname' => $flexFormContainerFieldName,
            'data-flexformcontaineridentifier' => $flexFormContainerIdentifier,
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-flexformsectioncontainerisnew' => (string)$flexFormSectionContainerIsNew,
            'data-overridevalues' => GeneralUtility::jsonEncodeForHtmlAttribute($this->data['overrideValues'], false),
            'data-defaultvalues' => GeneralUtility::jsonEncodeForHtmlAttribute($this->data['defaultValues'], false),
        ];

        $resultArray['html'] =
            '<typo3-formengine-element-category ' . GeneralUtility::implodeAttributes(['recordFieldId' => $fieldId, 'treeWrapperId' => $treeWrapperId], true) . '>
                <div class="formengine-field-item t3js-formengine-field-item">
                    ' . $fieldInformationHtml . '
                    <div class="form-control-wrap">
                        <div class="form-wizards-wrap">
                            <div class="form-wizards-element">
                                <div class="typo3-tceforms-tree">
                                    <input ' . GeneralUtility::implodeAttributes(array_map('strval', $recordElementAttributes), true, true) . '/>
                                </div>
                                <div id="' . htmlspecialchars($treeWrapperId) . '" class="svg-tree-element" style="height: ' . $heightInPx . 'px;"></div>
                            </div>
                            ' . $fieldWizardHtml . '
                        </div>
                    </div>
                </div>
            </typo3-formengine-element-category>';

        // add necessary labels for tree header
        if ($showHeader) {
            $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:core/Resources/Private/Language/locallang_csh_corebe.xlf';
        }

        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/FormEngine/Element/CategoryElement');

        return $resultArray;
    }
}
