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

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Inline element entry container.
 *
 * This container is the entry step to rendering an inline element. It is created by SingleFieldContainer.
 *
 * The code creates the main structure for the single inline elements, initializes
 * the inlineData array, that is manipulated and also returned back in its manipulated state.
 * The "control" stuff of inline elements is rendered here, for example the "create new" button.
 *
 * For each existing inline relation an InlineRecordContainer is called for further processing.
 */
class InlineControlContainer extends AbstractContainer
{
    /**
     * Inline data array used in JS, returned as JSON object to frontend
     *
     * @var array
     */
    protected $inlineData = [];

    /**
     * @var array<int,JavaScriptModuleInstruction>
     */
    protected $javaScriptModules = [];

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

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly InlineStackProcessor $inlineStackProcessor,
        private readonly HashService $hashService,
    ) {}

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();

        $this->inlineData = $this->data['inlineData'];

        $this->inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $field = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];

        $resultArray = $this->initializeResultArray();

        $config = $parameterArray['fieldConf']['config'];
        $foreign_table = $config['foreign_table'];
        $isReadOnly = isset($config['readOnly']) && $config['readOnly'];
        $language = 0;
        if (BackendUtility::isTableLocalizable($table)) {
            $languageFieldName = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
            $language = isset($row[$languageFieldName][0]) ? (int)$row[$languageFieldName][0] : (int)($row[$languageFieldName] ?? 0);
        }

        // Add the current inline job to the structure stack
        $newStructureItem = [
            'table' => $table,
            'uid' => $row['uid'],
            'field' => $field,
            'config' => $config,
        ];
        // Extract FlexForm parts (if any) from element name, e.g. array('vDEF', 'lDEF', 'FlexField', 'vDEF')
        if (!empty($parameterArray['itemFormElName'])) {
            $flexFormParts = $this->extractFlexFormParts($parameterArray['itemFormElName']);
            if ($flexFormParts !== null) {
                $newStructureItem['flexform'] = $flexFormParts;
            }
        }
        $this->inlineStackProcessor->pushStableStructureItem($newStructureItem);

        // Transport the flexform DS identifier fields to the FormInlineAjaxController
        if (!empty($newStructureItem['flexform'])
            && isset($this->data['processedTca']['columns'][$field]['config']['dataStructureIdentifier'])
        ) {
            $config['dataStructureIdentifier'] = $this->data['processedTca']['columns'][$field]['config']['dataStructureIdentifier'];
        }

        // Hand over original returnUrl to FormInlineAjaxController. Needed if opening for instance a
        // nested element in a new view to then go back to the original returnUrl and not the url of
        // the inline ajax controller
        $config['originalReturnUrl'] = $this->data['returnUrl'];

        // e.g. data[<table>][<uid>][<field>]
        $nameForm = $this->inlineStackProcessor->getCurrentStructureFormPrefix();
        // e.g. data-<pid>-<table1>-<uid1>-<field1>-<table2>-<uid2>-<field2>
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);

        $inlineChildren = $parameterArray['fieldConf']['children'] ?? [];

        $config['inline']['first'] = $config['inline']['last'] = false;
        if (is_array($inlineChildren) && $inlineChildren !== []) {
            $firstChild = $inlineChildren[array_key_first($inlineChildren)] ?? null;
            if (isset($firstChild['databaseRow']['uid'])) {
                $config['inline']['first'] = $firstChild['databaseRow']['uid'];
            }
            $lastChild = $inlineChildren[array_key_last($inlineChildren)] ?? null;
            if (isset($lastChild['databaseRow']['uid'])) {
                $config['inline']['last'] = $lastChild['databaseRow']['uid'];
            }
        }

        $top = $this->inlineStackProcessor->getStructureLevel(0);

        $this->inlineData['config'][$nameObject] = [
            'table' => $foreign_table,
        ];
        $configJson = (string)json_encode($config);
        $this->inlineData['config'][$nameObject . '-' . $foreign_table] = [
            'min' => $config['minitems'],
            'max' => $config['maxitems'],
            'sortable' => $config['appearance']['useSortable'] ?? false,
            'top' => [
                'table' => $top['table'],
                'uid' => $top['uid'],
            ],
            'context' => [
                'config' => $configJson,
                'hmac' => $this->hashService->hmac($configJson, 'InlineContext'),
            ],
        ];
        $this->inlineData['nested'][$nameObject] = $this->data['tabAndInlineStack'];

        $uniqueMax = 0;
        $uniqueIds = [];

        if ($config['foreign_unique'] ?? false) {
            // Add inlineData['unique'] with JS unique configuration
            // @todo: Improve validation and throw an exception if type is neither select nor group here
            $type = ($config['selectorOrUniqueConfiguration']['config']['type'] ?? '') === 'select' ? 'select' : 'groupdb';
            foreach ($inlineChildren as $child) {
                // Determine used unique ids, skip not localized records
                if (!$child['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                    $value = $child['databaseRow'][$config['foreign_unique']];
                    // We're assuming there is only one connected value here for both select and group
                    if ($type === 'select') {
                        // A select field is an array of uids. See TcaSelectItems data provider for details.
                        // Pick first entry, ends up as eg. $value = 42.
                        $value = $value['0'] ?? [];
                    } else {
                        // A group field is an array of arrays containing uid + table + title + row.
                        // See TcaGroup data provider for details.
                        // Pick the first one (always on 0), and use uid + table only. Exclude title + row
                        // since the entire inlineData['unique'] array ends up in JavaScript in the end
                        // and we don't need and want the title and the entire row data in the frontend.
                        // Ends up as $value = [ 'uid' => '42', 'table' => 'tx_my_table' ]
                        $value = [
                            'uid' => $value[0]['uid'],
                            'table' => $value[0]['table'],
                        ];
                    }
                    // Note structure of $value is different in select vs. group: It's a uid for select, but an
                    // array with uid + table for group.
                    if (isset($child['databaseRow']['uid'])) {
                        $uniqueIds[$child['databaseRow']['uid']] = $value;
                    }
                }
            }
            $possibleRecords = $config['selectorOrUniquePossibleRecords'] ?? [];
            $possibleRecordsUidToTitle = [];
            foreach ($possibleRecords as $possibleRecord) {
                $possibleRecordsUidToTitle[$possibleRecord['value']] = $possibleRecord['label'];
            }
            $uniqueMax = ($config['appearance']['useCombination'] ?? false) || empty($possibleRecords) ? -1 : count($possibleRecords);
            $this->inlineData['unique'][$nameObject . '-' . $foreign_table] = [
                'max' => $uniqueMax,
                'used' => $uniqueIds,
                'type' => $type,
                'table' => $foreign_table,
                'elTable' => $config['selectorOrUniqueConfiguration']['foreignTable'] ?? '',
                'field' => $config['foreign_unique'] ?? '',
                'selector' => ($config['selectorOrUniqueConfiguration']['isSelector'] ?? false) ? $type : false,
                'possible' => $possibleRecordsUidToTitle,
            ];
        }

        $resultArray['inlineData'] = $this->inlineData;

        // @todo: It might be a good idea to have something like "isLocalizedRecord" or similar set by a data provider
        $uidOfDefaultRecord = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null] ?? 0;
        $isLocalizedParent = $language > 0
            && ($uidOfDefaultRecord[0] ?? $uidOfDefaultRecord) > 0
            && MathUtility::canBeInterpretedAsInteger($row['uid']);
        $numberOfFullLocalizedChildren = 0;
        $numberOfNotYetLocalizedChildren = 0;
        foreach ($inlineChildren as $child) {
            if (!$child['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $numberOfFullLocalizedChildren++;
            }
            if ($isLocalizedParent && $child['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $numberOfNotYetLocalizedChildren++;
            }
        }

        // Render the localization buttons if needed
        $localizationButtons = '';
        if ($numberOfNotYetLocalizedChildren) {
            // Add the "Localize all records" button before all child records:
            if (!empty($config['appearance']['showAllLocalizationLink'])) {
                $localizationButtons = ' ' . $this->getLevelInteractionButton('localize', $config);
            }
            // Add the "Synchronize with default language" button before all child records:
            if (!empty($config['appearance']['showSynchronizationLink'])) {
                $localizationButtons .= ' ' . $this->getLevelInteractionButton('synchronize', $config);
            }
        }

        // Define how to show the "Create new record" button - if there are more than maxitems, hide it
        if ($isReadOnly || $numberOfFullLocalizedChildren >= ($config['maxitems'] ?? 0) || ($uniqueMax > 0 && $numberOfFullLocalizedChildren >= $uniqueMax)) {
            $config['inline']['inlineNewButtonStyle'] = 'display: none;';
        }

        // Render the "new record" level button:
        $newRecordButton = '';
        // For b/w compatibility, "showNewRecordLink" - in contrast to the other show* options - defaults to TRUE
        if (!isset($config['appearance']['showNewRecordLink']) || $config['appearance']['showNewRecordLink']) {
            $newRecordButton = $this->getLevelInteractionButton('newRecord', $config);
        }

        $formGroupAttributes = [
            'class' => 'form-group',
            'id' => $nameObject,
            'data-uid' => (string)$row['uid'],
            'data-local-table' => (string)$top['table'],
            'data-local-field' => (string)$top['field'],
            'data-foreign-table' => (string)$foreign_table,
            'data-object-group' => $nameObject . '-' . $foreign_table,
            'data-form-field' => $nameForm,
            'data-appearance' => (string)json_encode($config['appearance'] ?? ''),
        ];

        // Wrap all inline fields of a record with a <div> (like a container)
        $html = '<div ' . GeneralUtility::implodeAttributes($formGroupAttributes, true) . '>';

        $fieldInformationResult = $this->renderFieldInformation();
        $html .= $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        // Add the level buttons before all child records:
        if (in_array($config['appearance']['levelLinksPosition'] ?? null, ['both', 'top'], true)) {
            $html .= '<div class="form-group t3js-formengine-validation-marker t3js-inline-controls">' . $newRecordButton . $localizationButtons . '</div>';
        }

        // If it's required to select from possible child records (reusable children), add a selector box
        if (!$isReadOnly && ($config['foreign_selector'] ?? false) && ($config['appearance']['showPossibleRecordsSelector'] ?? true) !== false) {
            if (($config['selectorOrUniqueConfiguration']['config']['type'] ?? false) === 'select') {
                $selectorBox = $this->renderPossibleRecordsSelectorTypeSelect($config, $uniqueIds);
            } else {
                $selectorBox = $this->renderPossibleRecordsSelectorTypeGroupDB($config);
            }
            $html .= $selectorBox . $localizationButtons;
        }

        $title = $languageService->sL(trim($parameterArray['fieldConf']['label'] ?? ''));
        $html .= '<div class="panel-group panel-hover" data-title="' . htmlspecialchars($title) . '" id="' . $nameObject . '_records">';

        $sortableRecordUids = [];
        foreach ($inlineChildren as $options) {
            $options['inlineParentUid'] = $row['uid'];
            $options['inlineFirstPid'] = $this->data['inlineFirstPid'];
            // @todo: this can be removed if this container no longer sets additional info to $config
            $options['inlineParentConfig'] = $config;
            $options['inlineData'] = $this->inlineData;
            $options['inlineStructure'] = $this->inlineStackProcessor->getStructure();
            $options['inlineExpandCollapseStateArray'] = $this->data['inlineExpandCollapseStateArray'];
            $options['renderType'] = 'inlineRecordContainer';
            $childResult = $this->nodeFactory->create($options)->render();
            $html .= $childResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, false);
            if (!$options['isInlineDefaultLanguageRecordInLocalizedParentContext'] && isset($options['databaseRow']['uid'])) {
                // Don't add record to list of "valid" uids if it is only the default
                // language record of a not yet localized child
                $sortableRecordUids[] = $options['databaseRow']['uid'];
            }
        }

        $html .= '</div>';

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);
        $html .= $fieldWizardHtml;

        // Add the level buttons after all child records:
        if (!$isReadOnly && in_array($config['appearance']['levelLinksPosition'] ?? false, ['both', 'bottom'], true)) {
            $html .= '<div class="form-group t3js-formengine-validation-marker t3js-inline-controls">' . $newRecordButton . $localizationButtons . '</div>';
        }
        if (is_array($config['customControls'] ?? false)) {
            $html .= '<div id="' . $nameObject . '_customControls">';
            foreach ($config['customControls'] as $customControlConfig) {
                if (!isset($customControlConfig['userFunc'])) {
                    throw new \RuntimeException('Support for customControl without a userFunc key in TCA type inline is not supported.', 1548052629);
                }
                $parameters = [
                    'table' => $table,
                    'field' => $field,
                    'row' => $row,
                    'nameObject' => $nameObject,
                    'nameForm' => $nameForm,
                    'config' => $config,
                    'customControlConfig' => $customControlConfig,
                    // Warning: By reference should be used with care here and exists mostly to allow additional $resultArray['javaScriptModules']
                    'resultArray' => &$resultArray,
                ];
                $html .= GeneralUtility::callUserFunction($customControlConfig['userFunc'], $parameters, $this);
            }
            $html .= '</div>';
        }
        $resultArray['javaScriptModules'] = array_merge($resultArray['javaScriptModules'], $this->javaScriptModules);
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/container/inline-control-container.js'
        )->instance($nameObject);

        // Publish the uids of the child records in the given order to the browser
        $html .= '<input type="hidden" name="' . $nameForm . '" value="' . implode(',', $sortableRecordUids) . '" '
            . ' data-formengine-validation-rules="'
            . htmlspecialchars($this->getValidationDataAsJsonString([
                'type' => 'inline',
                'minitems' => $config['minitems'] ?? null,
                'maxitems' => $config['maxitems'] ?? null,
            ]))
            . '"'
            . ' class="inlineRecord" />';
        // Close the wrap for all inline fields (container)
        $html .= '</div>';

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend($html);
        return $resultArray;
    }

    /**
     * Creates the HTML code of a general button to be used on a level of inline children.
     * The possible keys for the parameter $type are 'newRecord', 'localize' and 'synchronize'.
     *
     * @param string $type The button type, values are 'newRecord', 'localize' and 'synchronize'.
     * @param array $conf TCA configuration of the parent(!) field
     * @return string The HTML code of the new button, wrapped in a div
     */
    protected function getLevelInteractionButton(string $type, array $conf = []): string
    {
        $languageService = $this->getLanguageService();
        $attributes = [];
        switch ($type) {
            case 'newRecord':
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createnew'));
                $icon = 'actions-plus';
                $attributes['class'] = 'btn btn-default t3js-create-new-button';
                $attributes['data-type'] = 'newRecord';
                if (!empty($conf['inline']['inlineNewButtonStyle'])) {
                    $attributes['style'] = $conf['inline']['inlineNewButtonStyle'];
                }
                if (!empty($conf['appearance']['newRecordLinkAddTitle'])) {
                    $title = htmlspecialchars(sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createnew.link'),
                        $languageService->sL($GLOBALS['TCA'][$conf['foreign_table']]['ctrl']['title'])
                    ));
                } elseif (isset($conf['appearance']['newRecordLinkTitle']) && $conf['appearance']['newRecordLinkTitle'] !== '') {
                    $title = htmlspecialchars($languageService->sL($conf['appearance']['newRecordLinkTitle']));
                }
                break;
            case 'localize':
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localizeAllRecords'));
                $icon = 'actions-document-localize';
                $attributes['class'] = 'btn btn-default t3js-synchronizelocalize-button';
                $attributes['data-type'] = 'localize';
                break;
            case 'synchronize':
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:synchronizeWithOriginalLanguage'));
                $icon = 'actions-document-synchronize';
                $attributes['class'] = 'btn btn-default inlineNewButton t3js-synchronizelocalize-button';
                $attributes['data-type'] = 'synchronize';
                break;
            default:
                $title = '';
                $icon = '';
        }
        // Create the button:
        $icon = $icon ? $this->iconFactory->getIcon($icon, IconSize::SMALL)->render() : '';
        $attributes['title'] = $title;
        return '
            <button type="button" ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>
                ' . $icon . ' ' . $title . '
            </button>
        ';
    }

    /**
     * Generate a button that opens an element browser in a new window.
     * For group/db there is no way to use a "selector" like a <select>|</select>-box.
     *
     * @param array $inlineConfiguration TCA inline configuration of the parent(!) field
     * @return string A HTML button that opens an element browser in a new window
     */
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration): string
    {
        $languageService = $this->getLanguageService();
        $groupFieldConfiguration = $inlineConfiguration['selectorOrUniqueConfiguration']['config'];
        $objectPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $inlineConfiguration['foreign_table'];
        $elementBrowserEnabled = true;
        if (is_array($groupFieldConfiguration['appearance'] ?? null)
            && isset($inlineConfiguration['appearance']['elementBrowserEnabled'])
        ) {
            $elementBrowserEnabled = (bool)$inlineConfiguration['appearance']['elementBrowserEnabled'];
        }
        // Remove any white-spaces from the allowed extension lists
        $allowed = GeneralUtility::trimExplode(',', (string)($groupFieldConfiguration['allowed'] ?? ''), true);
        $buttonStyle = '';
        if (isset($inlineConfiguration['inline']['inlineNewRelationButtonStyle'])) {
            $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineNewRelationButtonStyle'] . '"';
        }
        $item = '';
        if ($elementBrowserEnabled) {
            if (!empty($inlineConfiguration['appearance']['createNewRelationLinkTitle'])) {
                $createNewRelationText = htmlspecialchars($languageService->sL($inlineConfiguration['appearance']['createNewRelationLinkTitle']));
            } else {
                $createNewRelationText = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createNewRelation'));
            }
            $item .= '
                <button type="button" class="btn btn-default t3js-element-browser" data-mode="db" data-params="' . htmlspecialchars('|||' . implode(',', $allowed) . '|' . $objectPrefix) . '"
                    ' . $buttonStyle . ' title="' . $createNewRelationText . '">
                    ' . $this->iconFactory->getIcon('actions-insert-record', IconSize::SMALL)->render() . '
                    ' . $createNewRelationText . '
                </button>';
        }
        $item = '<div class="form-control-wrap t3js-inline-controls">' . $item . '</div>';
        if (!empty($allowed)) {
            $item .= '
                <div class="form-text">
                    ' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.allowedRelations')) . '
                    <br>
                    <ul class="badge-list">
                    ' . implode(' ', array_map(static fn(string $item): string => '<li><span class="badge badge-success">' . strtoupper($item) . '</span></li>', $allowed)) . '
                    </ul>
                </div>';
        }
        return '<div class="form-group t3js-formengine-validation-marker">' . $item . '</div>';
    }

    /**
     * Get a selector as used for the select type, to select from all available
     * records and to create a relation to the embedding record (e.g. like MM).
     *
     * @param array $config TCA inline configuration of the parent(!) field
     * @param array $uniqueIds The uids that have already been used and should be unique
     * @return string A HTML <select> box with all possible records
     */
    protected function renderPossibleRecordsSelectorTypeSelect(array $config, array $uniqueIds)
    {
        $config += [
            'autoSizeMax' => 0,
            'foreign_table' => '',
        ];
        $possibleRecords = $config['selectorOrUniquePossibleRecords'];
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        // Create option tags:
        $opt = [];
        foreach ($possibleRecords as $possibleRecord) {
            if (!in_array($possibleRecord['value'], $uniqueIds)) {
                $opt[] = '<option value="' . htmlspecialchars($possibleRecord['value']) . '">' . htmlspecialchars($possibleRecord['label']) . '</option>';
            }
        }
        // Put together the selector box:
        $size = (int)($config['size'] ?? 0);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($possibleRecords) + 1, $size, $autoSizeMax);
        }

        $item = '
            <select id="' . $nameObject . '-' . $config['foreign_table'] . '_selector" class="form-select t3js-create-new-selector"' . ($size ? ' size="' . $size . '"' : '') . '>
                ' . implode('', $opt) . '
            </select>';

        if ($size <= 1) {
            // Add a "Create new relation" button for adding new relations
            // This is necessary, if the size of the selector is "1" or if
            // there is only one record item in the select-box, that is selected by default
            // The selector-box creates a new relation on using an onChange event (see some line above)
            if (!empty($config['appearance']['createNewRelationLinkTitle'])) {
                $createNewRelationText = htmlspecialchars($this->getLanguageService()->sL($config['appearance']['createNewRelationLinkTitle']));
            } else {
                $createNewRelationText = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createNewRelation'));
            }
            $item .= '
                <button type="button" class="btn btn-default t3js-create-new-button" title="' . $createNewRelationText . '">
                    ' . $this->iconFactory->getIcon('actions-plus', IconSize::SMALL)->render() . $createNewRelationText . '
                </button>';
        }

        // Wrap the selector and add a spacer to the bottom
        $item = '<div class="input-group form-group t3js-formengine-validation-marker t3js-inline-controls">' . $item . '</div>';
        return $item;
    }

    /**
     * Extracts FlexForm parts of a form element name like
     * data[table][uid][field][sDEF][lDEF][FlexForm][vDEF]
     * Helper method used in inline
     *
     * @param string $formElementName The form element name
     * @return array|null
     */
    protected function extractFlexFormParts($formElementName)
    {
        $flexFormParts = null;
        $matches = [];
        if (preg_match('#^data(?:\[[^]]+\]){3}(\[data\](?:\[[^]]+\]){4,})$#', $formElementName, $matches)) {
            $flexFormParts = GeneralUtility::trimExplode(
                '][',
                trim($matches[1], '[]')
            );
        }
        return $flexFormParts;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
