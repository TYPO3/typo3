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

use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of elements of the type "group"
 */
class GroupElement extends AbstractFormElement
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
     * Default field controls for this element.
     *
     * @var array
     */
    protected $defaultFieldControl = [
        'elementBrowser' => [
            'renderType' => 'elementBrowser',
        ],
        'insertClipboard' => [
            'renderType' => 'insertClipboard',
            'after' => [ 'elementBrowser' ],
        ],
        'editPopup' => [
            'renderType' => 'editPopup',
            'disabled' => true,
            'after' => [ 'insertClipboard' ],
        ],
        'addRecord' => [
            'renderType' => 'addRecord',
            'disabled' => true,
            'after' => [ 'editPopup' ],
        ],
        'listModule' => [
            'renderType' => 'listModule',
            'disabled' => true,
            'after' => [ 'addRecord' ],
        ],
    ];

    /**
     * Default field wizards for this element
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'tableList' => [
            'renderType' => 'tableList',
        ],
        'recordsOverview' => [
            'renderType' => 'recordsOverview',
            'after' => [ 'tableList' ],
        ],
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
            'after' => [ 'recordsOverview' ],
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [ 'localizationStateSelector' ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [ 'otherLanguageContent' ],
        ],
    ];

    /**
     * This will render a selector box into which elements from either
     * the file system or database can be inserted. Relations.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws \RuntimeException
     */
    public function render()
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $resultArray = $this->initializeResultArray();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        $selectedItems = $parameterArray['itemFormElValue'];
        $maxItems = $config['maxitems'];

        $size = (int)($config['size'] ?? 5);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectedItems) + 1, $size, $autoSizeMax);
        }

        $internalType = (string)($config['internal_type'] ?? 'db');
        $maxTitleLength = $backendUser->uc['titleLen'];

        $listOfSelectedValues = [];
        $selectorOptionsHtml = [];
        if ($internalType === 'folder') {
            foreach ($selectedItems as $selectedItem) {
                $folder = $selectedItem['folder'];
                $listOfSelectedValues[] = $folder;
                $selectorOptionsHtml[] =
                    '<option value="' . htmlspecialchars($folder) . '" title="' . htmlspecialchars($folder) . '">'
                        . htmlspecialchars($folder)
                    . '</option>';
            }
        } elseif ($internalType === 'db') {
            foreach ($selectedItems as $selectedItem) {
                $tableWithUid = $selectedItem['table'] . '_' . $selectedItem['uid'];
                $listOfSelectedValues[] = $tableWithUid;
                $title = $selectedItem['title'];
                if (empty($title)) {
                    $title = '[' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']';
                }
                $shortenedTitle = GeneralUtility::fixed_lgd_cs($title, $maxTitleLength);
                $selectorOptionsHtml[] =
                    '<option value="' . htmlspecialchars($tableWithUid) . '" title="' . htmlspecialchars($title) . '">'
                        . htmlspecialchars($this->appendValueToLabelInDebugMode($shortenedTitle, $tableWithUid))
                    . '</option>';
            }
        } else {
            throw new \RuntimeException(
                'Invalid TCA internal_type "' . $internalType . '" on type="group", field "' . $fieldName . '", table "' . $table . '"',
                1485007097
            );
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if (isset($config['readOnly']) && $config['readOnly']) {
            // Return early if element is read only
            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<select';
            $html[] =               ' size="' . $size . '"';
            $html[] =               ' disabled="disabled"';
            $html[] =               ' class="form-select"';
            $html[] =               ($maxItems !== 1 && $size !== 1) ? ' multiple="multiple"' : '';
            $html[] =           '>';
            $html[] =               implode(LF, $selectorOptionsHtml);
            $html[] =           '</select>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        // Need some information if in flex form scope for the suggest element
        $dataStructureIdentifier = '';
        $flexFormSheetName = '';
        $flexFormFieldName = '';
        $flexFormContainerName = '';
        $flexFormContainerFieldName = '';
        if ($this->data['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            $flexFormConfig = $this->data['processedTca']['columns'][$fieldName];
            $dataStructureIdentifier = $flexFormConfig['config']['dataStructureIdentifier'];
            if (!isset($flexFormConfig['config']['dataStructureIdentifier'])) {
                throw new \RuntimeException(
                    'A data structure identifier must be set in [\'config\'] part of a flex form.'
                    . ' This is usually added by TcaFlexPrepare data processor',
                    1485206970
                );
            }
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
        }
        // Get minimum characters for suggest from TCA and override by TsConfig
        $suggestMinimumCharacters = 0;
        if (isset($config['suggestOptions']['default']['minimumCharacters'])) {
            $suggestMinimumCharacters = (int)$config['suggestOptions']['default']['minimumCharacters'];
        }
        if (isset($parameterArray['fieldTSConfig']['suggest.']['default.']['minimumCharacters'])) {
            $suggestMinimumCharacters = (int)$parameterArray['fieldTSConfig']['suggest.']['default.']['minimumCharacters'];
        }
        $suggestMinimumCharacters = $suggestMinimumCharacters > 0 ? $suggestMinimumCharacters : 2;

        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $showMoveIcons = true;
        if (isset($config['hideMoveIcons']) && $config['hideMoveIcons']) {
            $showMoveIcons = false;
        }
        $showDeleteControl = true;
        if (isset($config['hideDeleteIcon']) && $config['hideDeleteIcon']) {
            $showDeleteControl = false;
        }

        $fieldId = StringUtility::getUniqueId('tceforms-multiselect-');

        $selectorAttributes = [
            'id' => $fieldId,
            'data-formengine-input-name' => htmlspecialchars($elementName),
            'data-maxitems' => (string)$maxItems,
            'size' => (string)$size,
        ];
        $selectorAttributes['class'] = 'form-select';
        if ($maxItems !== 1 && $size !== 1) {
            $selectorAttributes['multiple'] = 'multiple';
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        if ($internalType === 'db' && (!isset($config['hideSuggest']) || (bool)$config['hideSuggest'] !== true)) {
            $html[] =   '<div class="form-wizards-items-top">';
            $html[] =       '<div class="autocomplete t3-form-suggest-container">';
            $html[] =           '<div class="input-group">';
            $html[] =               '<span class="input-group-addon">';
            $html[] =                   $this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render();
            $html[] =               '</span>';
            $html[] =               '<input type="search" class="t3-form-suggest form-control"';
            $html[] =                   ' placeholder="' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.findRecord') . '"';
            $html[] =                   ' data-fieldname="' . htmlspecialchars($fieldName) . '"';
            $html[] =                   ' data-tablename="' . htmlspecialchars($table) . '"';
            $html[] =                   ' data-field="' . htmlspecialchars($elementName) . '"';
            $html[] =                   ' data-uid="' . htmlspecialchars($this->data['databaseRow']['uid']) . '"';
            $html[] =                   ' data-pid="' . htmlspecialchars($this->data['parentPageRow']['uid'] ?? 0) . '"';
            $html[] =                   ' data-fieldtype="' . htmlspecialchars($config['type']) . '"';
            $html[] =                   ' data-minchars="' . htmlspecialchars((string)$suggestMinimumCharacters) . '"';
            $html[] =                   ' data-datastructureidentifier="' . htmlspecialchars($dataStructureIdentifier) . '"';
            $html[] =                   ' data-flexformsheetname="' . htmlspecialchars($flexFormSheetName) . '"';
            $html[] =                   ' data-flexformfieldname="' . htmlspecialchars($flexFormFieldName) . '"';
            $html[] =                   ' data-flexformcontainername="' . htmlspecialchars($flexFormContainerName) . '"';
            $html[] =                   ' data-flexformcontainerfieldname="' . htmlspecialchars($flexFormContainerFieldName) . '"';
            $html[] =               '/>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
        }
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" class="t3js-group-hidden-field" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . $itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<select ' . GeneralUtility::implodeAttributes($selectorAttributes, true) . '>';
        $html[] =               implode(LF, $selectorOptionsHtml);
        $html[] =           '</select>';
        $html[] =       '</div>';
        if (($maxItems > 1 && $size > 1 && $showMoveIcons) || $showDeleteControl) {
            $html[] =       '<div class="form-wizards-items-aside form-wizards-items-aside--move">';
            $html[] =           '<div class="btn-group-vertical">';
            if ($maxItems > 1 && $size >= 5 && $showMoveIcons) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-top"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
            if ($maxItems > 1 && $size > 1 && $showMoveIcons) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-up"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-down"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
            if ($maxItems > 1 && $size >= 5 && $showMoveIcons) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-bottom"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
            if ($showDeleteControl) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-removeoption t3js-revert-unique"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' data-uid="' . htmlspecialchars($row['uid']) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
        }
        $html[] =           '</div>';
        $html[] =       '</div>';
        if ($fieldControlHtml !== '') {
            $html[] =       '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =           '<div class="btn-group-vertical">';
            $html[] =               $fieldControlHtml;
            $html[] =           '</div>';
            $html[] =       '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =   '</div>';

        $hiddenElementAttrs = array_merge(
            [
                'type' => 'hidden',
                'name' => $elementName,
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
                'value' => implode(',', $listOfSelectedValues),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        $html[] =   '<input ' . GeneralUtility::implodeAttributes($hiddenElementAttrs, true) . '>';
        $html[] = '</div>';

        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/Backend/FormEngine/Element/GroupElement'
        )->instance($fieldId);

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
