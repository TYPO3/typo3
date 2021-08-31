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

use TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Render a single inline record relation.
 *
 * This container is called by InlineControlContainer to render single existing records.
 * Furthermore it is called by FormEngine for an incoming ajax request to expand an existing record
 * or to create a new one.
 *
 * This container creates the outer HTML of single inline records - eg. drag and drop and delete buttons.
 * For rendering of the record itself processing is handed over to FullRecordContainer.
 */
class InlineRecordContainer extends AbstractContainer
{
    /**
     * Inline data array used for JSON output
     *
     * @var array
     */
    protected $inlineData = [];

    /**
     * @var InlineStackProcessor
     */
    protected $inlineStackProcessor;

    /**
     * Array containing instances of hook classes called once for IRRE objects
     *
     * @var array
     */
    protected $hookObjects = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Default constructor
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $this->initHookObjects();
    }

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws AccessDeniedContentEditException
     */
    public function render()
    {
        $data = $this->data;
        $this->inlineData = $data['inlineData'];

        $inlineStackProcessor = $this->inlineStackProcessor;
        $inlineStackProcessor->initializeByGivenStructure($data['inlineStructure']);

        $record = $data['databaseRow'];
        $inlineConfig = $data['inlineParentConfig'];
        $foreignTable = $inlineConfig['foreign_table'];

        $resultArray = $this->initializeResultArray();

        // Send a mapping information to the browser via JSON:
        // e.g. data[<curTable>][<curId>][<curField>] => data-<pid>-<parentTable>-<parentId>-<parentField>-<curTable>-<curId>-<curField>
        $formPrefix = $inlineStackProcessor->getCurrentStructureFormPrefix();
        $domObjectId = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid']);
        $this->inlineData['map'][$formPrefix] = $domObjectId;

        $resultArray['inlineData'] = $this->inlineData;

        // Get the current naming scheme for DOM name/id attributes:
        $appendFormFieldNames = '[' . $foreignTable . '][' . ($record['uid'] ?? 0) . ']';
        $objectId = $domObjectId . '-' . $foreignTable . '-' . ($record['uid'] ?? 0);
        $classes = [];
        $html = '';
        $combinationHtml = '';
        $isNewRecord = $data['command'] === 'new';
        $hiddenField = '';
        if (isset($data['processedTca']['ctrl']['enablecolumns']['disabled'])) {
            $hiddenField = $data['processedTca']['ctrl']['enablecolumns']['disabled'];
        }
        if (!$data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            if ($isNewRecord || $data['isInlineChildExpanded']) {
                // Render full content ONLY IF this is an AJAX request, a new record, or the record is not collapsed
                $combinationHtml = '';
                if (isset($data['combinationChild'])) {
                    $combinationChild = $this->renderCombinationChild($data, $appendFormFieldNames);
                    $combinationHtml = $combinationChild['html'];
                    $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $combinationChild, false);
                }
                $childArray = $this->renderChild($data);
                $html = $childArray['html'];
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray, false);
            } else {
                // This class is the marker for the JS-function to check if the full content has already been loaded
                $classes[] = 't3js-not-loaded';
            }
            if ($isNewRecord) {
                // Add pid of record as hidden field
                $html .= '<input type="hidden" name="data' . htmlspecialchars($appendFormFieldNames)
                    . '[pid]" value="' . htmlspecialchars($record['pid']) . '"/>';
                // Tell DataHandler this record is expanded
                $ucFieldName = 'uc[inlineView]'
                    . '[' . $data['inlineTopMostParentTableName'] . ']'
                    . '[' . $data['inlineTopMostParentUid'] . ']'
                    . $appendFormFieldNames;
                $html .= '<input type="hidden" name="' . htmlspecialchars($ucFieldName)
                    . '" value="' . (int)$data['isInlineChildExpanded'] . '" />';
            } else {
                // Set additional field for processing for saving
                $html .= '<input type="hidden" name="cmd' . htmlspecialchars($appendFormFieldNames)
                    . '[delete]" value="1" disabled="disabled" />';
                if (!$data['isInlineChildExpanded'] && !empty($hiddenField)) {
                    $checked = !empty($record[$hiddenField]) ? ' checked="checked"' : '';
                    $html .= '<input type="checkbox" data-formengine-input-name="data'
                        . htmlspecialchars($appendFormFieldNames)
                        . '[' . htmlspecialchars($hiddenField) . ']" value="1"' . $checked . ' />';
                    $html .= '<input type="input" name="data' . htmlspecialchars($appendFormFieldNames)
                        . '[' . htmlspecialchars($hiddenField) . ']" value="' . htmlspecialchars($record[$hiddenField]) . '" />';
                }
            }
            // If this record should be shown collapsed
            $classes[] = $data['isInlineChildExpanded'] ? 'panel-visible' : 'panel-collapsed';
        }
        $hiddenFieldHtml = implode(LF, $resultArray['additionalHiddenFields'] ?? []);

        if ($inlineConfig['renderFieldsOnly'] ?? false) {
            // Render "body" part only
            $html .= $combinationHtml;
        } else {
            // Render header row and content (if expanded)
            if ($data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $classes[] = 't3-form-field-container-inline-placeHolder';
            }
            if (!empty($hiddenField) && isset($record[$hiddenField]) && (int)$record[$hiddenField]) {
                $classes[] = 't3-form-field-container-inline-hidden';
            }
            if ($isNewRecord) {
                $classes[] = 'inlineIsNewRecord';
            }

            $originalUniqueValue = '';
            if (isset($record['uid'], $data['inlineData']['unique'][$domObjectId . '-' . $foreignTable]['used'][$record['uid']])) {
                $uniqueValueValues = $data['inlineData']['unique'][$domObjectId . '-' . $foreignTable]['used'][$record['uid']];
                // in case of site_language we don't have the full form engine options, so fallbacks need to be taken into account
                $originalUniqueValue = ($uniqueValueValues['table'] ?? $foreignTable) . '_';
                // @todo In what circumstance would $uniqueValueValues be an array that lacks a 'uid' key? Unclear, but
                // it breaks the string concatenation. This is a hacky workaround for type safety only.
                $uVV = ($uniqueValueValues['uid'] ?? $uniqueValueValues);
                if (is_array($uVV)) {
                    $uVV = implode(',', $uVV);
                }
                $originalUniqueValue .= $uVV;
            }

            // The hashed object id needs a non-numeric prefix, the value is used as ID selector in JavaScript
            $hashedObjectId = 'hash-' . md5($objectId);
            $containerAttributes = [
                'id' => $objectId . '_div',
                'class' => 'form-irre-object panel panel-default panel-condensed ' . trim(implode(' ', $classes)),
                'data-object-uid' => $record['uid'] ?? 0,
                'data-object-id' => $objectId,
                'data-object-id-hash' => $hashedObjectId,
                'data-object-parent-group' => $domObjectId . '-' . $foreignTable,
                'data-field-name' => $appendFormFieldNames,
                'data-topmost-parent-table' => $data['inlineTopMostParentTableName'],
                'data-topmost-parent-uid' => $data['inlineTopMostParentUid'],
                'data-table-unique-original-value' => $originalUniqueValue,
                'data-placeholder-record' => $data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ? '1' : '0'
            ];

            $ariaExpanded = ($data['isInlineChildExpanded'] ?? false) ? 'true' : 'false';
            $ariaControls = htmlspecialchars($objectId . '_fields', ENT_QUOTES | ENT_HTML5);
            $ariaAttributesString = 'aria-expanded="' . $ariaExpanded . '" aria-controls="' . $ariaControls . '"';
            $html = '
				<div ' . GeneralUtility::implodeAttributes($containerAttributes, true) . '>
					<div class="panel-heading" data-bs-toggle="formengine-inline" id="' . htmlspecialchars($hashedObjectId, ENT_QUOTES | ENT_HTML5) . '_header" data-expandSingle="' . (($inlineConfig['appearance']['expandSingle'] ?? false) ? 1 : 0) . '">
						<div class="form-irre-header">
							<div class="form-irre-header-cell form-irre-header-icon">
								<span class="caret"></span>
							</div>
							' . $this->renderForeignRecordHeader($data, $ariaAttributesString) . '
						</div>
					</div>
					<div class="panel-collapse" id="' . $ariaControls . '">' . $html . $hiddenFieldHtml . $combinationHtml . '</div>
				</div>';
        }

        $resultArray['html'] = $html;
        return $resultArray;
    }

    /**
     * Render inner child
     *
     * @param array $data
     * @return array Result array
     */
    protected function renderChild(array $data)
    {
        $domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid']);
        $data['tabAndInlineStack'][] = [
            'inline',
            $domObjectId . '-' . $data['tableName'] . '-' . $data['databaseRow']['uid'],
        ];
        // @todo: ugly construct ...
        $data['inlineData'] = $this->inlineData;
        $data['renderType'] = 'fullRecordContainer';
        return $this->nodeFactory->create($data)->render();
    }

    /**
     * Render child child
     *
     * Render a table with FormEngine, that occurs on an intermediate table but should be editable directly,
     * so two tables are combined (the intermediate table with attributes and the sub-embedded table).
     * -> This is a direct embedding over two levels!
     *
     * @param array $data
     * @param string $appendFormFieldNames The [<table>][<uid>] of the parent record (the intermediate table)
     * @return array Result array
     */
    protected function renderCombinationChild(array $data, $appendFormFieldNames)
    {
        $childData = $data['combinationChild'];
        $parentConfig = $data['inlineParentConfig'];

        // If field is set to readOnly, set all fields of the relation to readOnly as well
        if (isset($parentConfig['readOnly']) && $parentConfig['readOnly']) {
            foreach ($childData['processedTca']['columns'] as $columnName => $columnConfiguration) {
                $childData['processedTca']['columns'][$columnName]['config']['readOnly'] = true;
            }
        }

        $resultArray = $this->initializeResultArray();

        // Display Warning FlashMessage if it is not suppressed
        if (!isset($parentConfig['appearance']['suppressCombinationWarning']) || empty($parentConfig['appearance']['suppressCombinationWarning'])) {
            $combinationWarningMessage = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.inline_use_combination';
            if (!empty($parentConfig['appearance']['overwriteCombinationWarningMessage'])) {
                $combinationWarningMessage = $parentConfig['appearance']['overwriteCombinationWarningMessage'];
            }
            $message = $this->getLanguageService()->sL($combinationWarningMessage);
            $markup = [];
            // @TODO: This is not a FlashMessage! The markup must be changed and special CSS
            // @TODO: should be created, in order to prevent confusion.
            $markup[] = '<div class="alert alert-warning">';
            $markup[] = '    <div class="media">';
            $markup[] = '        <div class="media-left">';
            $markup[] = '            <span class="fa-stack fa-lg">';
            $markup[] = '                <i class="fa fa-circle fa-stack-2x"></i>';
            $markup[] = '                <i class="fa fa-exclamation fa-stack-1x"></i>';
            $markup[] = '            </span>';
            $markup[] = '        </div>';
            $markup[] = '        <div class="media-body">';
            $markup[] = '            <div class="alert-message">' . htmlspecialchars($message) . '</div>';
            $markup[] = '        </div>';
            $markup[] = '    </div>';
            $markup[] = '</div>';
            $resultArray['html'] = implode(LF, $markup);
        }

        $childArray = $this->renderChild($childData);
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);

        // If this is a new record, add a pid value to store this record and the pointer value for the intermediate table
        if ($childData['command'] === 'new') {
            $comboFormFieldName = 'data[' . $childData['tableName'] . '][' . $childData['databaseRow']['uid'] . '][pid]';
            $resultArray['html'] .= '<input type="hidden" name="' . htmlspecialchars($comboFormFieldName) . '" value="' . htmlspecialchars($childData['databaseRow']['pid']) . '" />';
        }
        // If the foreign_selector field is also responsible for uniqueness, tell the browser the uid of the "other" side of the relation
        if ($childData['command'] === 'new' || $parentConfig['foreign_unique'] === $parentConfig['foreign_selector']) {
            $parentFormFieldName = 'data' . $appendFormFieldNames . '[' . $parentConfig['foreign_selector'] . ']';
            $resultArray['html'] .= '<input type="hidden" name="' . htmlspecialchars($parentFormFieldName) . '" value="' . htmlspecialchars($childData['databaseRow']['uid']) . '" />';
        }

        return $resultArray;
    }

    /**
     * Renders the HTML header for a foreign record, such as the title, toggle-function, drag'n'drop, etc.
     * Later on the command-icons are inserted here.
     *
     * @param array $data Current data
     * @param string $ariaAttributesString HTML aria attributes for the collapse button
     * @return string The HTML code of the header
     */
    protected function renderForeignRecordHeader(array $data, string $ariaAttributesString)
    {
        $languageService = $this->getLanguageService();
        $inlineConfig = $data['inlineParentConfig'];
        $foreignTable = $inlineConfig['foreign_table'];
        $rec = $data['databaseRow'];
        // Init:
        $domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid']);
        $objectId = $domObjectId . '-' . $foreignTable . '-' . ($rec['uid'] ?? 0);

        $recordTitle = $data['recordTitle'];
        if (!empty($recordTitle)) {
            // The user function may return HTML, therefore we can't escape it
            if (empty($data['processedTca']['ctrl']['formattedLabel_userFunc'])) {
                $recordTitle = BackendUtility::getRecordTitlePrep($recordTitle);
            }
        } else {
            $recordTitle = '<em>[' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</em>';
        }

        $altText = BackendUtility::getRecordIconAltText($rec, $foreignTable);

        $iconImg = '<span title="' . $altText . '" id="' . htmlspecialchars($objectId) . '_icon">' . $this->iconFactory->getIconForRecord($foreignTable, $rec, Icon::SIZE_SMALL)->render() . '</span>';
        $label = '<span id="' . $objectId . '_label">' . $recordTitle . '</span>';
        $ctrl = $this->renderForeignRecordHeaderControl($data);
        $thumbnail = false;

        // Renders a thumbnail for the header
        if (($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && !empty($inlineConfig['appearance']['headerThumbnail']['field'])) {
            $fieldValue = $rec[$inlineConfig['appearance']['headerThumbnail']['field']];
            $fileUid = $fieldValue[0]['uid'];

            if (!empty($fileUid)) {
                try {
                    $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
                } catch (\InvalidArgumentException $e) {
                    $fileObject = null;
                }
                if ($fileObject && $fileObject->isMissing()) {
                    $thumbnail .= '<span class="label label-danger">'
                        . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'))
                        . '</span>&nbsp;' . htmlspecialchars($fileObject->getName()) . '<br />';
                } elseif ($fileObject) {
                    $imageSetup = $inlineConfig['appearance']['headerThumbnail'] ?? [];
                    unset($imageSetup['field']);
                    $cropVariantCollection = CropVariantCollection::create($rec['crop'] ?? '');
                    if (!$cropVariantCollection->getCropArea()->isEmpty()) {
                        $imageSetup['crop'] = $cropVariantCollection->getCropArea()->makeAbsoluteBasedOnFile($fileObject);
                    }
                    $imageSetup = array_merge(['maxWidth' => '145', 'maxHeight' => '45'], $imageSetup);

                    if (($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && $fileObject->isImage()) {
                        $processedImage = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageSetup);
                        // Only use a thumbnail if the processing process was successful by checking if image width is set
                        if ($processedImage->getProperty('width')) {
                            $imageUrl = $processedImage->getPublicUrl() ?? '';
                            $thumbnail = '<img src="' . htmlspecialchars($imageUrl) . '" ' .
                                'width="' . $processedImage->getProperty('width') . '" ' .
                                'height="' . $processedImage->getProperty('height') . '" ' .
                                'alt="' . htmlspecialchars($altText) . '" ' .
                                'title="' . htmlspecialchars($altText) . '">';
                        }
                    } else {
                        $thumbnail = '';
                    }
                }
            }
        }

        if (!empty($inlineConfig['appearance']['headerThumbnail']['field']) && $thumbnail) {
            $mediaContainer = '<div class="form-irre-header-thumbnail" id="' . $objectId . '_thumbnailcontainer">' . $thumbnail . '</div>';
        } else {
            $mediaContainer = '<div class="form-irre-header-icon" id="' . $objectId . '_iconcontainer">' . $iconImg . '</div>';
        }
        $header = '<button class="form-irre-header-cell form-irre-header-button" ' . $ariaAttributesString . '>' .
            $mediaContainer .
            '<div class="form-irre-header-body">' . $label . '</div>' .
            '</button>' .
            '<div class="form-irre-header-cell form-irre-header-control t3js-formengine-irre-control">' . $ctrl . '</div>';

        return $header;
    }

    /**
     * Render the control-icons for a record header (create new, sorting, delete, disable/enable).
     * Most of the parts are copy&paste from TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList and
     * modified for the JavaScript calls here
     *
     * @param array $data Current data
     * @return string The HTML code with the control-icons
     */
    protected function renderForeignRecordHeaderControl(array $data)
    {
        $rec = $data['databaseRow'];
        $rec += [
            'uid' => 0,
            'table_local' => '',
            'sys_language_uid' => '',
        ];
        $inlineConfig = $data['inlineParentConfig'];
        $foreignTable = $inlineConfig['foreign_table'];
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        // Initialize:
        $cells = [
            'edit' => '',
            'hide' => '',
            'delete' => '',
            'info' => '',
            'new' => '',
            'sort.up' => '',
            'sort.down' => '',
            'dragdrop' => '',
            'localize' => '',
            'locked' => '',
        ];
        $isNewItem = strpos($rec['uid'], 'NEW') === 0;
        $isParentReadOnly = isset($inlineConfig['readOnly']) && $inlineConfig['readOnly'];
        $isParentExisting = MathUtility::canBeInterpretedAsInteger($data['inlineParentUid']);
        $tcaTableCtrl = $GLOBALS['TCA'][$foreignTable]['ctrl'];
        $tcaTableCols = $GLOBALS['TCA'][$foreignTable]['columns'];
        $isPagesTable = $foreignTable === 'pages';
        $isSysFileReferenceTable = $foreignTable === 'sys_file_reference';
        $enableManualSorting = ($tcaTableCtrl['sortby'] ?? false)
            || ($inlineConfig['MM'] ?? false)
            || (!($data['isOnSymmetricSide'] ?? false) && ($inlineConfig['foreign_sortby'] ?? false))
            || (($data['isOnSymmetricSide'] ?? false) && ($inlineConfig['symmetric_sortby'] ?? false));
        $calcPerms = new Permission($backendUser->calcPerms(BackendUtility::readPageAccess((int)($data['parentPageRow']['uid'] ?? 0), $backendUser->getPagePermsClause(Permission::PAGE_SHOW))));
        // If the listed table is 'pages' we have to request the permission settings for each page:
        $localCalcPerms = new Permission(Permission::NOTHING);
        if ($isPagesTable) {
            $localCalcPerms = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $rec['uid'])));
        }
        // This expresses the edit permissions for this particular element:
        $permsEdit = ($isPagesTable && $localCalcPerms->editPagePermissionIsGranted()) || (!$isPagesTable && $calcPerms->editContentPermissionIsGranted());
        // Controls: Defines which controls should be shown
        $enabledControls = $inlineConfig['appearance']['enabledControls'];
        // Hook: Can disable/enable single controls for specific child records:
        foreach ($this->hookObjects as $hookObj) {
            /** @var InlineElementHookInterface $hookObj */
            $hookObj->renderForeignRecordHeaderControl_preProcess($data['inlineParentUid'], $foreignTable, $rec, $inlineConfig, $data['isInlineDefaultLanguageRecordInLocalizedParentContext'], $enabledControls);
        }
        if ($data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            $cells['localize'] = '<span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localize.isLocalizable')) . '">
                    ' . $this->iconFactory->getIcon('actions-edit-localize-status-low', Icon::SIZE_SMALL)->render() . '
                </span>';
        }
        // "Info": (All records)
        // @todo: hardcoded sys_file!
        if ($rec['table_local'] === 'sys_file') {
            $uid = $rec['uid_local'][0]['uid'];
            $table = '_FILE';
        } else {
            $uid = $rec['uid'];
            $table = $foreignTable;
        }
        if ($enabledControls['info']) {
            if ($isNewItem) {
                $cells['info'] = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
            } else {
                $cells['info'] = '
				<button type="button" class="btn btn-default" data-action="infowindow" data-info-table="' . htmlspecialchars($table) . '" data-info-uid="' . htmlspecialchars($uid) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo')) . '">
					' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '
				</button>';
            }
        }
        // If the table is NOT a read-only table, then show these links:
        if (!$isParentReadOnly && !($tcaTableCtrl['readOnly'] ?? false) && !($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false)) {
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
            if (($enabledControls['new'] ?? false) && ($enableManualSorting || ($tcaTableCtrl['useColumnsForDefaultValues'] ?? false))) {
                if ((!$isPagesTable && $calcPerms->editContentPermissionIsGranted()) || ($isPagesTable && $calcPerms->createPagePermissionIsGranted())) {
                    $style = '';
                    if ($inlineConfig['inline']['inlineNewButtonStyle'] ?? false) {
                        $style = ' style="' . $inlineConfig['inline']['inlineNewButtonStyle'] . '"';
                    }
                    $cells['new'] = '
                        <button type="button" class="btn btn-default t3js-create-new-button" data-record-uid="' . htmlspecialchars($rec['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:new' . ($isPagesTable ? 'Page' : 'Record'))) . '" ' . $style . '>
                            ' . $this->iconFactory->getIcon('actions-' . ($isPagesTable ? 'page-new' : 'add'), Icon::SIZE_SMALL)->render() . '
                        </button>';
                }
            }
            // "Up/Down" links
            if ($enabledControls['sort'] && $permsEdit && $enableManualSorting) {
                // Up
                $icon = 'actions-move-up';
                $class = '';
                if ($inlineConfig['inline']['first'] == $rec['uid']) {
                    $class = ' disabled';
                    $icon = 'empty-empty';
                }
                $cells['sort.up'] = '
                    <button type="button" class="btn btn-default' . $class . '" data-action="sort" data-direction="up" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:moveUp')) . '">
                        ' . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render() . '
                    </button>';
                // Down
                $icon = 'actions-move-down';
                $class = '';
                if ($inlineConfig['inline']['last'] == $rec['uid']) {
                    $class = ' disabled';
                    $icon = 'empty-empty';
                }

                $cells['sort.down'] = '
                    <button type="button" class="btn btn-default' . $class . '" data-action="sort" data-direction="down" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:moveDown')) . '">
                        ' . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render() . '
                    </button>';
            }
            // "Edit" link:
            if (($rec['table_local'] === 'sys_file') && !$isNewItem && $backendUser->check('tables_modify', 'sys_file_metadata')) {
                $sys_language_uid = 0;
                if (!empty($rec['sys_language_uid'])) {
                    $sys_language_uid = $rec['sys_language_uid'][0];
                }
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('sys_file_metadata');
                $recordInDatabase = $queryBuilder
                    ->select('uid')
                    ->from('sys_file_metadata')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'file',
                            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)
                        )
                    )
                    ->setMaxResults(1)
                    ->execute()
                    ->fetchAssociative();
                if (!empty($recordInDatabase)) {
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                        'edit[sys_file_metadata][' . (int)$recordInDatabase['uid'] . ']' => 'edit',
                        'returnUrl' => $this->data['returnUrl']
                    ]);
                    $title = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata');
                    $cells['edit'] = '
                        <a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($title) . '">
                            ' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '
                        </a>';
                }
            }
            // "Delete" link:
            if ($enabledControls['delete'] && (($isPagesTable && $localCalcPerms->deletePagePermissionIsGranted())
                    || (!$isPagesTable && $calcPerms->editContentPermissionIsGranted())
                    || ($isSysFileReferenceTable && $calcPerms->editPagePermissionIsGranted()))
            ) {
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:delete'));
                $icon = $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render();
                $cells['delete'] = '<button type="button" class="btn btn-default t3js-editform-delete-inline-record" title="' . $title . '">' . $icon . '</button>';
            }

            // "Hide/Unhide" links:
            $hiddenField = $tcaTableCtrl['enablecolumns']['disabled'] ?? '';
            if (($enabledControls['hide'] ?? false)
                && $permsEdit
                && $hiddenField
                && $tcaTableCols[$hiddenField]
                && (!($tcaTableCols[$hiddenField]['exclude'] ?? false) || $backendUser->check('non_exclude_fields', $foreignTable . ':' . $hiddenField))
            ) {
                if ($rec[$hiddenField]) {
                    $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:unHide' . ($isPagesTable ? 'Page' : '')));
                    $cells['hide'] = '
                        <button type="button" class="btn btn-default t3js-toggle-visibility-button" data-hidden-field="' . htmlspecialchars($hiddenField) . '" title="' . $title . '">
                            ' . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '
                        </button>';
                } else {
                    $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:hide' . ($isPagesTable ? 'Page' : '')));
                    $cells['hide'] = '
                        <button type="button" class="btn btn-default t3js-toggle-visibility-button" data-hidden-field="' . htmlspecialchars($hiddenField) . '" title="' . $title . '">
                            ' . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '
                        </button>';
                }
            }
            // Drag&Drop Sorting: Sortable handler for script.aculo.us
            if (($enabledControls['dragdrop'] ?? false) && $permsEdit && $enableManualSorting && ($inlineConfig['appearance']['useSortable'] ?? false)) {
                $cells['dragdrop'] = '
                    <span class="btn btn-default sortableHandle" data-id="' . htmlspecialchars($rec['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move')) . '">
                        ' . $this->iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '
                    </span>';
            }
        } elseif (($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false) && $isParentExisting) {
            if (($enabledControls['localize'] ?? false) && ($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false)) {
                $cells['localize'] = '
                    <button type="button" class="btn btn-default t3js-synchronizelocalize-button" data-type="' . htmlspecialchars($rec['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localize')) . '">
                        ' . $this->iconFactory->getIcon('actions-document-localize', Icon::SIZE_SMALL)->render() . '
                    </button>';
            }
        }
        // If the record is edit-locked by another user, we will show a little warning sign:
        if ($lockInfo = BackendUtility::isRecordLocked($foreignTable, $rec['uid'])) {
            $cells['locked'] = '
				<button type="button" class="btn btn-default" data-bs-toggle="tooltip" title="' . htmlspecialchars($lockInfo['msg']) . '">
					' . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render() . '
				</button>';
        }
        // Hook: Post-processing of single controls for specific child records:
        foreach ($this->hookObjects as $hookObj) {
            $hookObj->renderForeignRecordHeaderControl_postProcess($data['inlineParentUid'], $foreignTable, $rec, $inlineConfig, $data['isInlineDefaultLanguageRecordInLocalizedParentContext'], $cells);
        }

        $out = '';
        if (!empty($cells['edit']) || !empty($cells['hide']) || !empty($cells['delete'])) {
            $out .= '<div class="btn-group btn-group-sm" role="group">' . $cells['edit'] . $cells['hide'] . $cells['delete'] . '</div>';
            unset($cells['edit'], $cells['hide'], $cells['delete']);
        }
        if (!empty($cells['info']) || !empty($cells['new']) || !empty($cells['sort.up']) || !empty($cells['sort.down']) || !empty($cells['dragdrop'])) {
            $out .= '<div class="btn-group btn-group-sm" role="group">' . $cells['info'] . $cells['new'] . $cells['sort.up'] . $cells['sort.down'] . $cells['dragdrop'] . '</div>';
            unset($cells['info'], $cells['new'], $cells['sort.up'], $cells['sort.down'], $cells['dragdrop']);
        }
        if (!empty($cells['localize'])) {
            $out .= '<div class="btn-group btn-group-sm" role="group">' . $cells['localize'] . '</div>';
            unset($cells['localize']);
        }
        if (!empty($cells)) {
            $out .= ' <div class="btn-group btn-group-sm" role="group">' . implode('', $cells) . '</div>';
        }
        return $out;
    }

    /**
     * Initialized the hook objects for this class.
     * Each hook object has to implement the interface
     * \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface
     *
     * @throws \UnexpectedValueException
     */
    protected function initHookObjects()
    {
        $this->hookObjects = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'] ?? [] as $className) {
            $processObject = GeneralUtility::makeInstance($className);
            if (!$processObject instanceof InlineElementHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . InlineElementHookInterface::class, 1202072000);
            }
            $this->hookObjects[] = $processObject;
        }
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
