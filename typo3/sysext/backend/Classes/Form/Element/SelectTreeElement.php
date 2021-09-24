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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeInterface;
use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render data as a tree.
 *
 * Typically rendered for config type=select, renderType=selectTree
 */
class SelectTreeElement extends AbstractFormElement
{
    use OnFieldChangeTrait;

    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * @var array Default wizards
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
    ];

    /**
     * Default number of tree nodes to show (determines tree height)
     * when no ['config']['size'] is set
     *
     * @var int
     */
    protected $itemsToShow = 15;

    /**
     * Number of items to show at last
     * e.g. when you have only 2 items in a tree
     *
     * @var int
     */
    protected $minItemsToShow = 5;

    /**
     * Pixel height of a single tree node
     *
     * @var int
     */
    protected $itemHeight = 20;

    /**
     * Render tree widget
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @see AbstractNode::initializeResultArray()
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $formElementId = md5($parameterArray['itemFormElName']);

        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];
        $readOnly = !empty($config['readOnly']) && $config['readOnly'];
        $exclusiveKeys = !empty($config['exclusiveKeys']) ? $config['exclusiveKeys'] : '';
        $exclusiveKeys = $exclusiveKeys . ',';
        $appearance = !empty($config['treeConfig']['appearance']) ? $config['treeConfig']['appearance'] : [];
        $expanded = !empty($appearance['expandAll']);
        $showHeader = !empty($appearance['showHeader']);
        if (isset($config['size']) && (int)$config['size'] > 0) {
            $height = max($this->minItemsToShow, (int)$config['size']);
        } else {
            $height = $this->itemsToShow;
        }
        $heightInPx = $height * $this->itemHeight;
        $treeWrapperId = 'tree_' . $formElementId;
        $fieldId = 'tree_record_' . $formElementId;

        $fieldName = $this->data['fieldName'];

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

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               '<div class="typo3-tceforms-tree">';
        $html[] =                   '<input class="treeRecord" type="hidden" id="' . htmlspecialchars($fieldId) . '"';
        $html[] =                       ' data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $html[] =                       ' data-relatedfieldname="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] =                       ' data-tablename="' . htmlspecialchars($this->data['tableName']) . '"';
        $html[] =                       ' data-fieldname="' . htmlspecialchars($this->data['fieldName']) . '"';
        $html[] =                       ' data-uid="' . (int)$this->data['vanillaUid'] . '"';
        $html[] =                       ' data-recordtypevalue="' . htmlspecialchars($this->data['recordTypeValue']) . '"';
        $html[] =                       ' data-datastructureidentifier="' . htmlspecialchars($dataStructureIdentifier) . '"';
        $html[] =                       ' data-flexformsheetname="' . htmlspecialchars($flexFormSheetName) . '"';
        $html[] =                       ' data-flexformfieldname="' . htmlspecialchars($flexFormFieldName) . '"';
        $html[] =                       ' data-flexformcontainername="' . htmlspecialchars($flexFormContainerName) . '"';
        $html[] =                       ' data-flexformcontaineridentifier="' . htmlspecialchars($flexFormContainerIdentifier) . '"';
        $html[] =                       ' data-flexformcontainerfieldname="' . htmlspecialchars($flexFormContainerFieldName) . '"';
        $html[] =                       ' data-flexformsectioncontainerisnew="' . htmlspecialchars((string)$flexFormSectionContainerIsNew) . '"';
        $html[] =                       ' data-command="' . htmlspecialchars($this->data['command']) . '"';
        $html[] =                       ' data-read-only="' . ($readOnly ? '1' : '0') . '"';
        $html[] =                       ' data-tree-exclusive-keys="' . htmlspecialchars($exclusiveKeys) . '"';
        $html[] =                       ' data-tree-expand-up-to-level="' . ($expanded ? '999' : '1') . '"';
        $html[] =                       ' data-tree-show-toolbar="' . $showHeader . '"';
        $html[] =                       ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] =                       ' id="treeinput' . $formElementId . '"';
        $html[] =                       ' value="' . htmlspecialchars(implode(',', $parameterArray['itemFormElValue'])) . '"';
        $html[] =                   '/>';
        $html[] =               '</div>';
        $html[] =               '<div id="' . $treeWrapperId . '" class="svg-tree-element" style="height: ' . $heightInPx . 'px;"></div>';
        $html[] =           '</div>';
        if (!$readOnly && !empty($fieldWizardHtml)) {
            $html[] =       '<div class="form-wizards-items-bottom">';
            $html[] =           $fieldWizardHtml;
            $html[] =       '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        // add necessary labels for tree header
        if ($showHeader) {
            $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:core/Resources/Private/Language/locallang_csh_corebe.xlf';
        }
        $fieldChangeFuncs = $this->getFieldChangeFuncs();
        if ($this->validateOnFieldChange($fieldChangeFuncs)) {
            $onFieldChangeItems = $this->getOnFieldChangeItems($fieldChangeFuncs);
            $resultArray['requireJsModules']['selectTreeElement'] = ['TYPO3/CMS/Backend/FormEngine/Element/SelectTreeElement' => '
                function(tree) {
                    new tree.SelectTreeElement(' . GeneralUtility::quoteJSvalue($treeWrapperId) . ', ' . GeneralUtility::quoteJSvalue($fieldId) . ', null, ' . json_encode($onFieldChangeItems, JSON_HEX_APOS | JSON_HEX_QUOT) . ');
                }',
            ];
        } else {
            // @deprecated
            $resultArray['requireJsModules']['selectTreeElement'] = ['TYPO3/CMS/Backend/FormEngine/Element/SelectTreeElement' => '
                function(tree) {
                    new tree.SelectTreeElement(' . GeneralUtility::quoteJSvalue($treeWrapperId) . ', ' . GeneralUtility::quoteJSvalue($fieldId) . ', ' . implode(';', $fieldChangeFuncs) . ');
                }',
            ];
        }

        return $resultArray;
    }

    /**
     * @return string[]|OnFieldChangeInterface[]
     */
    protected function getFieldChangeFuncs(): array
    {
        $items = [];
        $parameterArray = $this->data['parameterArray'];
        if (!empty($parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'])) {
            $items[] = $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
        }
        if (!empty($parameterArray['fieldChangeFunc']['alert'])) {
            $items[] = $parameterArray['fieldChangeFunc']['alert'];
        }
        return $items;
    }
}
