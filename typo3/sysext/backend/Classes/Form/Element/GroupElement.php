<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of elements of the type "group"
 */
class GroupElement extends AbstractFormElement
{
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
        'fileTypeList' => [
            'renderType' => 'fileTypeList',
            'after' => [ 'tableList' ],
        ],
        'fileThumbnails' => [
            'renderType' => 'fileThumbnails',
            'after' => [ 'fileTypeList' ],
        ],
        'recordsOverview' => [
            'renderType' => 'recordsOverview',
            'after' => [ 'fileThumbnails' ],
        ],
        'fileUpload' => [
            'renderType' => 'fileUpload',
            'after' => [ 'recordsOverview' ],
        ],
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
            'after' => [ 'fileUpload' ],
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
        $selectedItemsCount = count($selectedItems);

        $maxItems = $config['maxitems'];
        $autoSizeMax = MathUtility::forceIntegerInRange($config['autoSizeMax'], 0);
        $size = 5;
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        }
        if ($autoSizeMax >= 1) {
            $size = MathUtility::forceIntegerInRange($selectedItemsCount + 1, MathUtility::forceIntegerInRange($size, 1), $autoSizeMax);
        }

        $internalType = (string)$config['internal_type'];
        $maxTitleLength = $backendUser->uc['titleLen'];

        $listOfSelectedValues = [];
        $selectorOptionsHtml = [];
        if ($internalType === 'file_reference' || $internalType === 'file') {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
            foreach ($selectedItems as $selectedItem) {
                $uidOrPath = $selectedItem['uidOrPath'];
                $listOfSelectedValues[] = $uidOrPath;
                $title = $selectedItem['title'];
                $shortenedTitle = GeneralUtility::fixed_lgd_cs($title, $maxTitleLength);
                $selectorOptionsHtml[] =
                    '<option value="' . htmlspecialchars($uidOrPath) . '" title="' . htmlspecialchars($title) . '">'
                        . htmlspecialchars($this->appendValueToLabelInDebugMode($shortenedTitle, $uidOrPath))
                    . '</option>';
            }
        } elseif ($internalType === 'folder') {
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
                'internal_type missing on type="group" field',
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
            $html[] =               ' class="form-control tceforms-multiselect"';
            $html[] =               ($maxItems !== 1 && $size !== 1) ? ' multiple="multiple"' : '';
            $html[] =           '>';
            $html[] =               implode(LF, $selectorOptionsHtml);
            $html[] =           '</select>';
            $html[] =       '</div>';
            $html[] =       '<div class="form-wizards-items-aside">';
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

        // Check against inline uniqueness - Create some onclick js for delete control and element browser
        // to override record selection in some FAL scenarios - See 'appearance' docs of group element
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $deleteControlOnClick = '';
        if ($this->data['isInlineChild']
            && $this->data['inlineParentUid']
            && $this->data['inlineParentConfig']['foreign_table'] === $table
            && $this->data['inlineParentConfig']['foreign_unique'] === $fieldName
        ) {
            $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $table;
            $deleteControlOnClick = 'inline.revertUnique(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',null,' . GeneralUtility::quoteJSvalue($row['uid']) . ');';
        }

        $selectorAttributes = [
            'id' => StringUtility::getUniqueId('tceforms-multiselect-'),
            'data-formengine-input-name' => htmlspecialchars($elementName),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'size' => $size,
        ];
        $selectorClasses = [
            'form-control',
            'tceforms-multiselect',
        ];
        if ($maxItems === 1) {
            $selectorClasses[] = 'form-select-no-siblings';
        }
        $selectorAttributes['class'] = implode(' ', $selectorClasses);
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
            $html[] =                   ' data-pid="' . htmlspecialchars($this->data['effectivePid']) . '"';
            $html[] =                   ' data-fieldtype="' . htmlspecialchars($config['type']) . '"';
            $html[] =                   ' data-minchars="' . htmlspecialchars($suggestMinimumCharacters) . '"';
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
        $html[] =       '<div class="form-wizards-items-aside">';
        $html[] =           '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >=5 && $showMoveIcons) {
            $html[] =           '<a href="#"';
            $html[] =               ' class="btn btn-default t3js-btn-moveoption-top"';
            $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =           '>';
            $html[] =               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
            $html[] =           '</a>';
        }
        if ($maxItems > 1 && $size > 1 && $showMoveIcons) {
            $html[] =           '<a href="#"';
            $html[] =               ' class="btn btn-default t3js-btn-moveoption-up"';
            $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =           '>';
            $html[] =               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
            $html[] =           '</a>';
            $html[] =           '<a href="#"';
            $html[] =               ' class="btn btn-default t3js-btn-moveoption-down"';
            $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =           '>';
            $html[] =               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
            $html[] =           '</a>';
        }
        if ($maxItems > 1 && $size >= 5 && $showMoveIcons) {
            $html[] =           '<a href="#"';
            $html[] =               ' class="btn btn-default t3js-btn-moveoption-bottom"';
            $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =           '>';
            $html[] =               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
            $html[] =           '</a>';
        }
        if ($showDeleteControl) {
            $html[] =           '<a href="#"';
            $html[] =               ' class="btn btn-default t3js-btn-removeoption"';
            $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
            $html[] =               ' onClick="' . $deleteControlOnClick . '"';
            $html[] =           '>';
            $html[] =               $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
            $html[] =           '</a>';
        }
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =       '<div class="form-wizards-items-aside">';
        $html[] =           '<div class="btn-group-vertical">';
        $html[] =               $fieldControlHtml;
        $html[] =           '</div>';
        $html[] =       '</div>';
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =   '</div>';
        $html[] =   '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
        $html[] = '</div>';

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
