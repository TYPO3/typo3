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

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

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
     * @var InlineStackProcessor
     */
    protected $inlineStackProcessor;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var string[]
     */
    protected $requireJsModules = [];

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
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $this->inlineData = $this->data['inlineData'];

        /** @var InlineStackProcessor $inlineStackProcessor */
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $this->inlineStackProcessor = $inlineStackProcessor;
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $field = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];

        $resultArray = $this->initializeResultArray();

        $config = $parameterArray['fieldConf']['config'];
        $foreign_table = $config['foreign_table'];
        $isReadOnly = isset($config['readOnly']) && $config['readOnly'];
        $language = 0;
        $languageFieldName = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        if (BackendUtility::isTableLocalizable($table)) {
            $language = isset($row[$languageFieldName][0]) ? (int)$row[$languageFieldName][0] : (int)$row[$languageFieldName];
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
        $inlineStackProcessor->pushStableStructureItem($newStructureItem);

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
        $nameForm = $inlineStackProcessor->getCurrentStructureFormPrefix();
        // e.g. data-<pid>-<table1>-<uid1>-<field1>-<table2>-<uid2>-<field2>
        $nameObject = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);

        $config['inline']['first'] = false;
        $firstChild = reset($this->data['parameterArray']['fieldConf']['children']);
        if (isset($firstChild['databaseRow']['uid'])) {
            $config['inline']['first'] = $firstChild['databaseRow']['uid'];
        }
        $config['inline']['last'] = false;
        $lastChild = end($this->data['parameterArray']['fieldConf']['children']);
        if (isset($lastChild['databaseRow']['uid'])) {
            $config['inline']['last'] = $lastChild['databaseRow']['uid'];
        }

        $top = $inlineStackProcessor->getStructureLevel(0);

        $this->inlineData['config'][$nameObject] = [
            'table' => $foreign_table,
            'md5' => md5($nameObject)
        ];
        $configJson = json_encode($config);
        $this->inlineData['config'][$nameObject . '-' . $foreign_table] = [
            'min' => $config['minitems'],
            'max' => $config['maxitems'],
            'sortable' => $config['appearance']['useSortable'],
            'top' => [
                'table' => $top['table'],
                'uid' => $top['uid']
            ],
            'context' => [
                'config' => $configJson,
                'hmac' => GeneralUtility::hmac($configJson, 'InlineContext'),
            ],
        ];
        $this->inlineData['nested'][$nameObject] = $this->data['tabAndInlineStack'];

        $uniqueMax = 0;
        $uniqueIds = [];

        if ($config['foreign_unique']) {
            // Add inlineData['unique'] with JS unique configuration
            // @todo: Improve validation and throw an exception if type is neither select nor group here
            $type = $config['selectorOrUniqueConfiguration']['config']['type'] === 'select' ? 'select' : 'groupdb';
            foreach ($parameterArray['fieldConf']['children'] as $child) {
                // Determine used unique ids, skip not localized records
                if (!$child['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                    $value = $child['databaseRow'][$config['foreign_unique']];
                    // We're assuming there is only one connected value here for both select and group
                    if ($type === 'select') {
                        // A select field is an array of uids. See TcaSelectItems data provider for details.
                        // Pick first entry, ends up as eg. $value = 42.
                        $value = $value['0'];
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
                    // array with uid + table for group. This is handled differently on JavaScript side, search
                    // for 'groupdb' in jsfunc.inline.js for details.
                    $uniqueIds[$child['databaseRow']['uid']] = $value;
                }
            }
            $possibleRecords = $config['selectorOrUniquePossibleRecords'];
            $possibleRecordsUidToTitle = [];
            foreach ($possibleRecords as $possibleRecord) {
                $possibleRecordsUidToTitle[$possibleRecord[1]] = $possibleRecord[0];
            }
            $uniqueMax = $config['appearance']['useCombination'] || empty($possibleRecords) ? -1 : count($possibleRecords);
            $this->inlineData['unique'][$nameObject . '-' . $foreign_table] = [
                'max' => $uniqueMax,
                'used' => $uniqueIds,
                'type' => $type,
                'table' => $foreign_table,
                'elTable' => $config['selectorOrUniqueConfiguration']['foreignTable'],
                'field' => $config['foreign_unique'],
                'selector' => $config['selectorOrUniqueConfiguration']['isSelector'] ? $type : false,
                'possible' => $possibleRecordsUidToTitle,
            ];
        }

        $resultArray['inlineData'] = $this->inlineData;

        // @todo: It might be a good idea to have something like "isLocalizedRecord" or similar set by a data provider
        $uidOfDefaultRecord = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
        $isLocalizedParent = $language > 0
            && ($uidOfDefaultRecord[0] ?? $uidOfDefaultRecord) > 0
            && MathUtility::canBeInterpretedAsInteger($row['uid']);
        $numberOfFullLocalizedChildren = 0;
        $numberOfNotYetLocalizedChildren = 0;
        foreach ($this->data['parameterArray']['fieldConf']['children'] as $child) {
            if (!$child['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $numberOfFullLocalizedChildren++;
            }
            if ($isLocalizedParent && $child['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $numberOfNotYetLocalizedChildren++;
            }
        }

        // Render the localization links if needed
        $localizationLinks = '';
        if ($numberOfNotYetLocalizedChildren) {
            // Add the "Localize all records" link before all child records:
            if (isset($config['appearance']['showAllLocalizationLink']) && $config['appearance']['showAllLocalizationLink']) {
                $localizationLinks = ' ' . $this->getLevelInteractionLink('localize', $nameObject . '-' . $foreign_table, $config);
            }
            // Add the "Synchronize with default language" link before all child records:
            if (isset($config['appearance']['showSynchronizationLink']) && $config['appearance']['showSynchronizationLink']) {
                $localizationLinks .= ' ' . $this->getLevelInteractionLink('synchronize', $nameObject . '-' . $foreign_table, $config);
            }
        }

        // Define how to show the "Create new record" link - if there are more than maxitems, hide it
        if ($isReadOnly || $numberOfFullLocalizedChildren >= $config['maxitems'] || ($uniqueMax > 0 && $numberOfFullLocalizedChildren >= $uniqueMax)) {
            $config['inline']['inlineNewButtonStyle'] = 'display: none;';
            $config['inline']['inlineNewRelationButtonStyle'] = 'display: none;';
            $config['inline']['inlineOnlineMediaAddButtonStyle'] = 'display: none;';
        }

        // Render the level links (create new record):
        $levelLinks = '';
        if (!empty($config['appearance']['enabledControls']['new'])) {
            $levelLinks = $this->getLevelInteractionLink('newRecord', $nameObject . '-' . $foreign_table, $config);
        }
        // Wrap all inline fields of a record with a <div> (like a container)
        $html = '<div class="form-group" id="' . $nameObject . '">';

        $fieldInformationResult = $this->renderFieldInformation();
        $html .= $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        // Add the level links before all child records:
        if ($config['appearance']['levelLinksPosition'] === 'both' || $config['appearance']['levelLinksPosition'] === 'top') {
            $html .= '<div class="form-group t3js-formengine-validation-marker">' . $levelLinks . $localizationLinks . '</div>';
        }

        // If it's required to select from possible child records (reusable children), add a selector box
        if (!$isReadOnly && $config['foreign_selector'] && $config['appearance']['showPossibleRecordsSelector'] !== false) {
            if ($config['selectorOrUniqueConfiguration']['config']['type'] === 'select') {
                $selectorBox = $this->renderPossibleRecordsSelectorTypeSelect($config, $uniqueIds);
            } else {
                $selectorBox = $this->renderPossibleRecordsSelectorTypeGroupDB($config);
            }
            $html .= $selectorBox . $localizationLinks;
        }

        $title = $languageService->sL(trim($parameterArray['fieldConf']['label']));
        $html .= '<div class="panel-group panel-hover" data-title="' . htmlspecialchars($title) . '" id="' . $nameObject . '_records">';

        $sortableRecordUids = [];
        foreach ($this->data['parameterArray']['fieldConf']['children'] as $options) {
            $options['inlineParentUid'] = $row['uid'];
            $options['inlineFirstPid'] = $this->data['inlineFirstPid'];
            // @todo: this can be removed if this container no longer sets additional info to $config
            $options['inlineParentConfig'] = $config;
            $options['inlineData'] = $this->inlineData;
            $options['inlineStructure'] = $inlineStackProcessor->getStructure();
            $options['inlineExpandCollapseStateArray'] = $this->data['inlineExpandCollapseStateArray'];
            $options['renderType'] = 'inlineRecordContainer';
            $childResult = $this->nodeFactory->create($options)->render();
            $html .= $childResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, false);
            if (!$options['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
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

        // Add the level links after all child records:
        if (!$isReadOnly && ($config['appearance']['levelLinksPosition'] === 'both' || $config['appearance']['levelLinksPosition'] === 'bottom')) {
            $html .= $levelLinks . $localizationLinks;
        }
        if (is_array($config['customControls'])) {
            $html .= '<div id="' . $nameObject . '_customControls">';
            foreach ($config['customControls'] as $customControlConfig) {
                if (!isset($customControlConfig['userFunc'])) {
                    trigger_error('Support for customControl without a userFunc key will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
                    $customControlConfig = [
                        'userFunc' => $customControlConfig
                    ];
                }
                $parameters = [
                    'table' => $table,
                    'field' => $field,
                    'row' => $row,
                    'nameObject' => $nameObject,
                    'nameForm' => $nameForm,
                    'config' => $config,
                    'customControlConfig' => $customControlConfig,
                ];
                $html .= GeneralUtility::callUserFunction($customControlConfig['userFunc'], $parameters, $this);
            }
            $html .= '</div>';
        }
        // Add Drag&Drop functions for sorting to FormEngine::$additionalJS_post
        if (count($sortableRecordUids) > 1 && $config['appearance']['useSortable']) {
            $resultArray['additionalJavaScriptPost'][] = 'inline.createDragAndDropSorting("' . $nameObject . '_records' . '");';
        }
        $resultArray['requireJsModules'] = array_merge($resultArray['requireJsModules'], $this->requireJsModules);

        // Publish the uids of the child records in the given order to the browser
        $html .= '<input type="hidden" name="' . $nameForm . '" value="' . implode(',', $sortableRecordUids) . '" '
            . ' data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString(['type' => 'inline', 'minitems' => $config['minitems'], 'maxitems' => $config['maxitems']])) . '"'
            . ' class="inlineRecord" />';
        // Close the wrap for all inline fields (container)
        $html .= '</div>';

        $resultArray['html'] = $html;
        return $resultArray;
    }

    /**
     * Creates the HTML code of a general link to be used on a level of inline children.
     * The possible keys for the parameter $type are 'newRecord', 'localize' and 'synchronize'.
     *
     * @param string $type The link type, values are 'newRecord', 'localize' and 'synchronize'.
     * @param string $objectPrefix The "path" to the child record to create (e.g. 'data-parentPageId-partenTable-parentUid-parentField-childTable]')
     * @param array $conf TCA configuration of the parent(!) field
     * @return string The HTML code of the new link, wrapped in a div
     */
    protected function getLevelInteractionLink($type, $objectPrefix, $conf = [])
    {
        $languageService = $this->getLanguageService();
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $attributes = [];
        switch ($type) {
            case 'newRecord':
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createnew'));
                $icon = 'actions-add';
                $className = 'typo3-newRecordLink';
                $attributes['class'] = 'btn btn-default inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'];
                $attributes['onclick'] = 'return inline.createNewRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ')';
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
                $className = 'typo3-localizationLink';
                $attributes['class'] = 'btn btn-default';
                $attributes['onclick'] = 'return inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($objectPrefix) . ', \'localize\')';
                break;
            case 'synchronize':
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:synchronizeWithOriginalLanguage'));
                $icon = 'actions-document-synchronize';
                $className = 'typo3-synchronizationLink';
                $attributes['class'] = 'btn btn-default inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'];
                $attributes['onclick'] = 'return inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($objectPrefix) . ', \'synchronize\')';
                break;
            default:
                $title = '';
                $icon = '';
                $className = '';
        }
        // Create the link:
        $icon = $icon ? $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render() : '';
        $link = $this->wrapWithAnchor($icon . ' ' . $title, '#', $attributes);
        return '<div' . ($className ? ' class="' . $className . '"' : '') . 'title="' . $title . '">' . $link . '</div>';
    }

    /**
     * Wraps a text with an anchor and returns the HTML representation.
     *
     * @param string $text The text to be wrapped by an anchor
     * @param string $link  The link to be used in the anchor
     * @param array $attributes Array of attributes to be used in the anchor
     * @return string The wrapped text as HTML representation
     */
    protected function wrapWithAnchor($text, $link, $attributes = [])
    {
        $attributes['href'] = trim($link ?: '#');
        return '<a ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>' . $text . '</a>';
    }

    /**
     * Generate a link that opens an element browser in a new window.
     * For group/db there is no way to use a "selector" like a <select>|</select>-box.
     *
     * @param array $inlineConfiguration TCA inline configuration of the parent(!) field
     * @return string A HTML link that opens an element browser in a new window
     */
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration)
    {
        $backendUser = $this->getBackendUserAuthentication();
        $languageService = $this->getLanguageService();

        $groupFieldConfiguration = $inlineConfiguration['selectorOrUniqueConfiguration']['config'];

        $foreign_table = $inlineConfiguration['foreign_table'];
        $allowed = $groupFieldConfiguration['allowed'];
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;
        $nameObject = $currentStructureDomObjectIdPrefix;
        $mode = 'db';
        $showUpload = false;
        $elementBrowserEnabled = true;
        if (!empty($inlineConfiguration['appearance']['createNewRelationLinkTitle'])) {
            $createNewRelationText = htmlspecialchars($languageService->sL($inlineConfiguration['appearance']['createNewRelationLinkTitle']));
        } else {
            $createNewRelationText = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createNewRelation'));
        }
        if (is_array($groupFieldConfiguration['appearance'])) {
            if (isset($groupFieldConfiguration['appearance']['elementBrowserType'])) {
                $mode = $groupFieldConfiguration['appearance']['elementBrowserType'];
            }
            if ($mode === 'file') {
                $showUpload = true;
            }
            if (isset($inlineConfiguration['appearance']['fileUploadAllowed'])) {
                $showUpload = (bool)$inlineConfiguration['appearance']['fileUploadAllowed'];
            }
            if (isset($groupFieldConfiguration['appearance']['elementBrowserAllowed'])) {
                $allowed = $groupFieldConfiguration['appearance']['elementBrowserAllowed'];
            }
            if (isset($inlineConfiguration['appearance']['elementBrowserEnabled'])) {
                $elementBrowserEnabled = (bool)$inlineConfiguration['appearance']['elementBrowserEnabled'];
            }
        }
        // Remove any white-spaces from the allowed extension lists
        $allowed = implode(',', GeneralUtility::trimExplode(',', $allowed, true));
        $browserParams = '|||' . $allowed . '|' . $objectPrefix . '|inline.checkUniqueElement||inline.importElement';
        $onClick = 'setFormValueOpenBrowser(' . GeneralUtility::quoteJSvalue($mode) . ', ' . GeneralUtility::quoteJSvalue($browserParams) . '); return false;';

        $buttonStyle = '';
        if (isset($inlineConfiguration['inline']['inlineNewRelationButtonStyle'])) {
            $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineNewRelationButtonStyle'] . '"';
        }
        $item = '';
        if ($elementBrowserEnabled) {
            $item .= '
			<a href="#" class="btn btn-default inlineNewRelationButton ' . $this->inlineData['config'][$nameObject]['md5'] . '"
				' . $buttonStyle . ' onclick="' . htmlspecialchars($onClick) . '" title="' . $createNewRelationText . '">
				' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)->render() . '
				' . $createNewRelationText . '
			</a>';
        }

        $isDirectFileUploadEnabled = (bool)$backendUser->uc['edit_docModuleUpload'];
        $allowedArray = GeneralUtility::trimExplode(',', $allowed, true);
        $onlineMediaAllowed = OnlineMediaHelperRegistry::getInstance()->getSupportedFileExtensions();
        if (!empty($allowedArray)) {
            $onlineMediaAllowed = array_intersect($allowedArray, $onlineMediaAllowed);
        }
        if ($showUpload && $isDirectFileUploadEnabled) {
            $folder = $backendUser->getDefaultUploadFolder(
                $this->data['tableName'] === 'pages' ? $this->data['vanillaUid'] : $this->data['parentPageRow']['uid'],
                $this->data['tableName'],
                $this->data['fieldName']
            );
            if (
                $folder instanceof Folder
                && $folder->getStorage()->checkUserActionPermission('add', 'File')
            ) {
                $maxFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
                $item .= ' <a href="#" class="btn btn-default t3js-drag-uploader inlineNewFileUploadButton ' . $this->inlineData['config'][$nameObject]['md5'] . '"
					' . $buttonStyle . '
					data-dropzone-target="#' . htmlspecialchars(StringUtility::escapeCssSelector($currentStructureDomObjectIdPrefix)) . '"
					data-insert-dropzone-before="1"
					data-file-irre-object="' . htmlspecialchars($objectPrefix) . '"
					data-file-allowed="' . htmlspecialchars($allowed) . '"
					data-target-folder="' . htmlspecialchars($folder->getCombinedIdentifier()) . '"
					data-max-file-size="' . htmlspecialchars($maxFileSize) . '"
					>';
                $item .= $this->iconFactory->getIcon('actions-upload', Icon::SIZE_SMALL)->render() . ' ';
                $item .= htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.select-and-submit'));
                $item .= '</a>';

                $this->requireJsModules[] = ['TYPO3/CMS/Backend/DragUploader' => 'function(dragUploader){dragUploader.initialize()}'];
                if (!empty($onlineMediaAllowed)) {
                    $buttonStyle = '';
                    if (isset($inlineConfiguration['inline']['inlineOnlineMediaAddButtonStyle'])) {
                        $buttonStyle = ' style="' . $inlineConfiguration['inline']['inlineOnlineMediaAddButtonStyle'] . '"';
                    }
                    $this->requireJsModules[] = 'TYPO3/CMS/Backend/OnlineMedia';
                    $buttonText = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.button'));
                    $placeholder = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.placeholder'));
                    $buttonSubmit = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.submit'));
                    $allowedMediaUrl = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.allowEmbedSources'));
                    $item .= '
						<span class="btn btn-default t3js-online-media-add-btn ' . $this->inlineData['config'][$nameObject]['md5'] . '"
							' . $buttonStyle . '
							data-file-irre-object="' . htmlspecialchars($objectPrefix) . '"
							data-online-media-allowed="' . htmlspecialchars(implode(',', $onlineMediaAllowed)) . '"
							data-online-media-allowed-help-text="' . $allowedMediaUrl . '"
							data-target-folder="' . htmlspecialchars($folder->getCombinedIdentifier()) . '"
							title="' . $buttonText . '"
							data-btn-submit="' . $buttonSubmit . '"
							data-placeholder="' . $placeholder . '"
							>
							' . $this->iconFactory->getIcon('actions-online-media-add', Icon::SIZE_SMALL)->render() . '
							' . $buttonText . '</span>';
                }
            }
        }

        $item = '<div class="form-control-wrap">' . $item . '</div>';
        $allowedList = '';
        $allowedLabelKey = ($mode === 'file') ? 'allowedFileExtensions' : 'allowedRelations';
        $allowedLabel = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . $allowedLabelKey));
        foreach ($allowedArray as $allowedItem) {
            $allowedList .= '<span class="label label-success">' . strtoupper($allowedItem) . '</span> ';
        }
        if (!empty($allowedList)) {
            $item .= '<div class="help-block">' . $allowedLabel . '<br>' . $allowedList . '</div>';
        }
        $item = '<div class="form-group t3js-formengine-validation-marker">' . $item . '</div>';
        return $item;
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
        $possibleRecords = $config['selectorOrUniquePossibleRecords'];
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        // Create option tags:
        $opt = [];
        foreach ($possibleRecords as $p) {
            if (!in_array($p[1], $uniqueIds)) {
                $opt[] = '<option value="' . htmlspecialchars($p[1]) . '">' . htmlspecialchars($p[0]) . '</option>';
            }
        }
        // Put together the selector box:
        $size = (int)$config['size'];
        $size = $config['autoSizeMax'] ? MathUtility::forceIntegerInRange(count($possibleRecords) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax']) : $size;
        $onChange = 'return inline.importNewRecord(' . GeneralUtility::quoteJSvalue($nameObject . '-' . $config['foreign_table']) . ')';
        $item = '
            <select id="' . $nameObject . '-' . $config['foreign_table'] . '_selector" class="form-control"' . ($size ? ' size="' . $size . '"' : '')
            . ' onchange="' . htmlspecialchars($onChange) . '"' . ($config['foreign_unique'] ? ' isunique="isunique"' : '') . '>
                ' . implode('', $opt) . '
            </select>';

        if ($size <= 1) {
            // Add a "Create new relation" link for adding new relations
            // This is necessary, if the size of the selector is "1" or if
            // there is only one record item in the select-box, that is selected by default
            // The selector-box creates a new relation on using an onChange event (see some line above)
            if (!empty($config['appearance']['createNewRelationLinkTitle'])) {
                $createNewRelationText = htmlspecialchars($this->getLanguageService()->sL($config['appearance']['createNewRelationLinkTitle']));
            } else {
                $createNewRelationText = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createNewRelation'));
            }
            $item .= '
            <span class="input-group-btn">
                <a href="#" class="btn btn-default" onclick="' . htmlspecialchars($onChange) . '" title="' . $createNewRelationText . '">
                    ' . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . $createNewRelationText . '
                </a>
            </span>';
        } else {
            $item .= '
            <span class="input-group-btn btn"></span>';
        }

        // Wrap the selector and add a spacer to the bottom
        $item = '<div class="input-group form-group t3js-formengine-validation-marker ' . $this->inlineData['config'][$nameObject]['md5'] . '">' . $item . '</div>';
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
