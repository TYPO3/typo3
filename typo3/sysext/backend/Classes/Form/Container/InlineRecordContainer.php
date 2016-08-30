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

use TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Lang\LanguageService;

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

        // If there is a selector field, normalize it:
        if (!empty($inlineConfig['foreign_selector'])) {
            $foreign_selector = $inlineConfig['foreign_selector'];
            $valueToNormalize = $record[$foreign_selector];
            if (is_array($record[$foreign_selector])) {
                // @todo: this can be kicked again if always prepared rows are handled here
                $valueToNormalize = implode(',', $record[$foreign_selector]);
            }
            $record[$foreign_selector] = $this->normalizeUid($valueToNormalize);
        }

        // Get the current naming scheme for DOM name/id attributes:
        $appendFormFieldNames = '[' . $foreignTable . '][' . $record['uid'] . ']';
        $objectId = $domObjectId . '-' . $foreignTable . '-' . $record['uid'];
        $class = '';
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
                    $combinationChild['html'] = '';
                    $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $combinationChild);
                }
                $childArray = $this->renderChild($data);
                $html = $childArray['html'];
                $childArray['html'] = '';
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);
            } else {
                // This string is the marker for the JS-function to check if the full content has already been loaded
                $html = '<!--notloaded-->';
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
            $class = $data['isInlineChildExpanded'] ? 'panel-visible' : 'panel-collapsed';
        }
        if ($inlineConfig['renderFieldsOnly']) {
            // Render "body" part only
            $html = $html . $combinationHtml;
        } else {
            // Render header row and content (if expanded)
            if ($data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $class .= ' t3-form-field-container-inline-placeHolder';
            }
            if (!empty($hiddenField) && isset($record[$hiddenField]) && (int)$record[$hiddenField]) {
                $class .= ' t3-form-field-container-inline-hidden';
            }
            $class .= ($isNewRecord ? ' inlineIsNewRecord' : '');
            $html = '
				<div class="panel panel-default panel-condensed ' . trim($class) . '" id="' . htmlspecialchars($objectId) . '_div">
					<div class="panel-heading" data-toggle="formengine-inline" id="' . htmlspecialchars($objectId) . '_header" data-expandSingle="' . ($inlineConfig['appearance']['expandSingle'] ? 1 : 0) . '">
						<div class="form-irre-header">
							<div class="form-irre-header-cell form-irre-header-icon">
								<span class="caret"></span>
							</div>
							' . $this->renderForeignRecordHeader($data) . '
						</div>
					</div>
					<div class="panel-collapse" id="' . htmlspecialchars($objectId) . '_fields" data-expandSingle="' . ($inlineConfig['appearance']['expandSingle'] ? 1 : 0) . '" data-returnURL="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' . $html . $combinationHtml . '</div>
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

        $resultArray = $this->initializeResultArray();

        // Display Warning FlashMessage if it is not suppressed
        if (!isset($parentConfig['appearance']['suppressCombinationWarning']) || empty($parentConfig['appearance']['suppressCombinationWarning'])) {
            $combinationWarningMessage = 'LLL:EXT:lang/locallang_core.xlf:warning.inline_use_combination';
            if (!empty($parentConfig['appearance']['overwriteCombinationWarningMessage'])) {
                $combinationWarningMessage = $parentConfig['appearance']['overwriteCombinationWarningMessage'];
            }
            $message = $this->getLanguageService()->sL($combinationWarningMessage);
            $markup = [];
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
     * @return string The HTML code of the header
     */
    protected function renderForeignRecordHeader(array $data)
    {
        $languageService = $this->getLanguageService();
        $inlineConfig = $data['inlineParentConfig'];
        $foreignTable = $inlineConfig['foreign_table'];
        $rec = $data['databaseRow'];
        // Init:
        $domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid']);
        $objectId = $domObjectId . '-' . $foreignTable . '-' . $rec['uid'];

        $recordTitle = $data['recordTitle'];
        if (!empty($recordTitle)) {
            // The user function may return HTML, therefore we can't escape it
            if (empty($data['processedTca']['ctrl']['formattedLabel_userFunc'])) {
                $recordTitle = BackendUtility::getRecordTitlePrep($recordTitle);
            }
        } else {
            $recordTitle = '<em>[' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title')) . ']</em>';
        }

        $altText = BackendUtility::getRecordIconAltText($rec, $foreignTable);

        $iconImg = '<span title="' . $altText . '" id="' . htmlspecialchars($objectId) . '_icon' . '">' . $this->iconFactory->getIconForRecord($foreignTable, $rec, Icon::SIZE_SMALL)->render() . '</span>';
        $label = '<span id="' . $objectId . '_label">' . $recordTitle . '</span>';
        $ctrl = $this->renderForeignRecordHeaderControl($data);
        $thumbnail = false;

        // Renders a thumbnail for the header
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && !empty($inlineConfig['appearance']['headerThumbnail']['field'])) {
            $fieldValue = $rec[$inlineConfig['appearance']['headerThumbnail']['field']];
            $firstElement = array_shift(GeneralUtility::trimExplode('|', array_shift(GeneralUtility::trimExplode(',', $fieldValue))));
            $fileUid = array_pop(BackendUtility::splitTable_Uid($firstElement));

            if (!empty($fileUid)) {
                try {
                    $fileObject = ResourceFactory::getInstance()->getFileObject($fileUid);
                } catch (\InvalidArgumentException $e) {
                    $fileObject = null;
                }
                if ($fileObject && $fileObject->isMissing()) {
                    $thumbnail .= '<span class="label label-danger">'
                        . htmlspecialchars(static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing'))
                        . '</span>&nbsp;' . htmlspecialchars($fileObject->getName()) . '<br />';
                } elseif ($fileObject) {
                    $imageSetup = $inlineConfig['appearance']['headerThumbnail'];
                    unset($imageSetup['field']);
                    if (!empty($rec['crop'])) {
                        $imageSetup['crop'] = $rec['crop'];
                    }
                    $imageSetup = array_merge(['width' => '45', 'height' => '45c'], $imageSetup);
                    $processedImage = $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, $imageSetup);
                    // Only use a thumbnail if the processing process was successful by checking if image width is set
                    if ($processedImage->getProperty('width')) {
                        $imageUrl = $processedImage->getPublicUrl(true);
                        $thumbnail = '<img src="' . $imageUrl . '" ' .
                            'width="' . $processedImage->getProperty('width') . '" ' .
                            'height="' . $processedImage->getProperty('height') . '" ' .
                            'alt="' . htmlspecialchars($altText) . '" ' .
                            'title="' . htmlspecialchars($altText) . '">';
                    }
                }
            }
        }

        if (!empty($inlineConfig['appearance']['headerThumbnail']['field']) && $thumbnail) {
            $mediaContainer = '<div class="form-irre-header-cell form-irre-header-thumbnail" id="' . $objectId . '_thumbnailcontainer">' . $thumbnail . '</div>';
        } else {
            $mediaContainer = '<div class="form-irre-header-cell form-irre-header-icon" id="' . $objectId . '_iconcontainer">' . $iconImg . '</div>';
        }
        $header = $mediaContainer . '
				<div class="form-irre-header-cell form-irre-header-body">' . $label . '</div>
				<div class="form-irre-header-cell form-irre-header-control t3js-formengine-irre-control">' . $ctrl . '</div>';

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
        $inlineConfig = $data['inlineParentConfig'];
        $foreignTable = $inlineConfig['foreign_table'];
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        // Initialize:
        $cells = [];
        $additionalCells = [];
        $isNewItem = substr($rec['uid'], 0, 3) == 'NEW';
        $isParentExisting = MathUtility::canBeInterpretedAsInteger($data['inlineParentUid']);
        $tcaTableCtrl = $GLOBALS['TCA'][$foreignTable]['ctrl'];
        $tcaTableCols = $GLOBALS['TCA'][$foreignTable]['columns'];
        $isPagesTable = $foreignTable === 'pages';
        $isSysFileReferenceTable = $foreignTable === 'sys_file_reference';
        $enableManualSorting = $tcaTableCtrl['sortby'] || $inlineConfig['MM'] || !$data['isOnSymmetricSide']
            && $inlineConfig['foreign_sortby'] || $data['isOnSymmetricSide'] && $inlineConfig['symmetric_sortby'];
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid']);
        $nameObjectFt = $nameObject . '-' . $foreignTable;
        $nameObjectFtId = $nameObjectFt . '-' . $rec['uid'];
        $calcPerms = $backendUser->calcPerms(BackendUtility::readPageAccess($rec['pid'], $backendUser->getPagePermsClause(1)));
        // If the listed table is 'pages' we have to request the permission settings for each page:
        $localCalcPerms = false;
        if ($isPagesTable) {
            $localCalcPerms = $backendUser->calcPerms(BackendUtility::getRecord('pages', $rec['uid']));
        }
        // This expresses the edit permissions for this particular element:
        $permsEdit = $isPagesTable && $localCalcPerms & Permission::PAGE_EDIT || !$isPagesTable && $calcPerms & Permission::CONTENT_EDIT;
        // Controls: Defines which controls should be shown
        $enabledControls = $inlineConfig['appearance']['enabledControls'];
        // Hook: Can disable/enable single controls for specific child records:
        foreach ($this->hookObjects as $hookObj) {
            /** @var InlineElementHookInterface $hookObj */
            $hookObj->renderForeignRecordHeaderControl_preProcess($data['inlineParentUid'], $foreignTable, $rec, $inlineConfig, $data['isInlineDefaultLanguageRecordInLocalizedParentContext'], $enabledControls);
        }
        if ($data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            $cells['localize.isLocalizable'] = '<span title="' . $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localize.isLocalizable', true) . '">'
                . $this->iconFactory->getIcon('actions-edit-localize-status-low', Icon::SIZE_SMALL)->render()
                . '</span>';
        }
        // "Info": (All records)
        if ($enabledControls['info'] && !$isNewItem) {
            if ($rec['table_local'] === 'sys_file') {
                $uid = (int)substr($rec['uid_local'], 9);
                $table = '_FILE';
            } else {
                $uid = $rec['uid'];
                $table = $foreignTable;
            }
            $cells['info'] = '
				<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(('top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . GeneralUtility::quoteJSvalue($uid) . '); return false;')) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:showInfo', true) . '">
					' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '
				</a>';
        }
        // If the table is NOT a read-only table, then show these links:
        if (!$tcaTableCtrl['readOnly'] && !$data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
            if ($enabledControls['new'] && ($enableManualSorting || $tcaTableCtrl['useColumnsForDefaultValues'])) {
                if (!$isPagesTable && $calcPerms & Permission::CONTENT_EDIT || $isPagesTable && $calcPerms & Permission::PAGE_NEW) {
                    $onClick = 'return inline.createNewRecord(' . GeneralUtility::quoteJSvalue($nameObjectFt) . ',' . GeneralUtility::quoteJSvalue($rec['uid']) . ')';
                    $style = '';
                    if ($inlineConfig['inline']['inlineNewButtonStyle']) {
                        $style = ' style="' . $inlineConfig['inline']['inlineNewButtonStyle'] . '"';
                    }
                    $cells['new'] = '
						<a class="btn btn-default inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'] . '" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $languageService->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:new' . ($isPagesTable ? 'Page' : 'Record')), true) . '" ' . $style . '>
							' . $this->iconFactory->getIcon('actions-' . ($isPagesTable ? 'page' : 'document') . '-new', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
            }
            // "Up/Down" links
            if ($enabledControls['sort'] && $permsEdit && $enableManualSorting) {
                // Up
                $onClick = 'return inline.changeSorting(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ', \'1\')';
                $style = $inlineConfig['inline']['first'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
                $cells['sort.up'] = '
					<a class="btn btn-default sortingUp" href="#" onclick="' . htmlspecialchars($onClick) . '" ' . $style . ' title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveUp', true) . '">
						' . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '
					</a>';
                // Down
                $onClick = 'return inline.changeSorting(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ', \'-1\')';
                $style = $inlineConfig['inline']['last'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
                $cells['sort.down'] = '
					<a class="btn btn-default sortingDown" href="#" onclick="' . htmlspecialchars($onClick) . '" ' . $style . ' title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveDown', true) . '">
						' . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
            // "Edit" link:
            if (($rec['table_local'] === 'sys_file') && !$isNewItem) {
                $sys_language_uid = 0;
                if (!empty($rec['sys_language_uid'])) {
                    $sys_language_uid = $rec['sys_language_uid'][0];
                }
                $recordInDatabase = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'uid',
                    'sys_file_metadata',
                    'file = ' . (int)substr($rec['uid_local'], 9) . ' AND sys_language_uid = ' . $sys_language_uid
                );
                if ($backendUser->check('tables_modify', 'sys_file_metadata')) {
                    $url = BackendUtility::getModuleUrl('record_edit', [
                        'edit[sys_file_metadata][' . (int)$recordInDatabase['uid'] . ']' => 'edit',
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ]);
                    $title = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:cm.editMetadata');
                    $cells['editmetadata'] = '
						<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($title) . '">
							' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
            }
            // "Delete" link:
            if ($enabledControls['delete'] && ($isPagesTable && $localCalcPerms & Permission::PAGE_DELETE
                    || !$isPagesTable && $calcPerms & Permission::CONTENT_EDIT
                    || $isSysFileReferenceTable && $calcPerms & Permission::PAGE_EDIT)
            ) {
                $title = $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:delete', true);
                $icon = $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render();
                $cells['delete'] = '<a href="#" class="btn btn-default t3js-editform-delete-inline-record" data-objectid="' . htmlspecialchars($nameObjectFtId) . '" title="' . $title . '">' . $icon . '</a>';
            }

            // "Hide/Unhide" links:
            $hiddenField = $tcaTableCtrl['enablecolumns']['disabled'];
            if ($enabledControls['hide'] && $permsEdit && $hiddenField && $tcaTableCols[$hiddenField] && (!$tcaTableCols[$hiddenField]['exclude'] || $backendUser->check('non_exclude_fields', $foreignTable . ':' . $hiddenField))) {
                $onClick = 'return inline.enableDisableRecord(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ',' .
                    GeneralUtility::quoteJSvalue($hiddenField) . ')';
                $className = 't3js-' . $nameObjectFtId . '_disabled';
                if ($rec[$hiddenField]) {
                    $title = $languageService->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:unHide' . ($isPagesTable ? 'Page' : '')), true);
                    $cells['hide.unhide'] = '
						<a class="btn btn-default hiddenHandle ' . $className . '" href="#" onclick="'
                        . htmlspecialchars($onClick) . '"' . 'title="' . $title . '">' .
                        $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '
						</a>';
                } else {
                    $title = $languageService->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:hide' . ($isPagesTable ? 'Page' : '')), true);
                    $cells['hide.hide'] = '
						<a class="btn btn-default hiddenHandle ' . $className . '" href="#" onclick="'
                        . htmlspecialchars($onClick) . '"' . 'title="' . $title . '">' .
                        $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
            }
            // Drag&Drop Sorting: Sortable handler for script.aculo.us
            if ($enabledControls['dragdrop'] && $permsEdit && $enableManualSorting && $inlineConfig['appearance']['useSortable']) {
                $additionalCells['dragdrop'] = '
					<span class="btn btn-default sortableHandle" data-id="' . htmlspecialchars($rec['uid']) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move', true) . '">
						' . $this->iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '
					</span>';
            }
        } elseif ($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] && $isParentExisting) {
            if ($enabledControls['localize'] && $data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $onClick = 'inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($nameObjectFt) . ', ' . GeneralUtility::quoteJSvalue($rec['uid']) . ');';
                $cells['localize'] = '
					<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localize', true) . '">
						' . $this->iconFactory->getIcon('actions-document-localize', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
        }
        // If the record is edit-locked by another user, we will show a little warning sign:
        if ($lockInfo = BackendUtility::isRecordLocked($foreignTable, $rec['uid'])) {
            $cells['locked'] = '
				<a class="btn btn-default" href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg']) . ');return false;">
					' . '<span title="' . htmlspecialchars($lockInfo['msg']) . '">' . $this->iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>' . '
				</a>';
        }
        // Hook: Post-processing of single controls for specific child records:
        foreach ($this->hookObjects as $hookObj) {
            $hookObj->renderForeignRecordHeaderControl_postProcess($data['inlineParentUid'], $foreignTable, $rec, $inlineConfig, $data['isInlineDefaultLanguageRecordInLocalizedParentContext'], $cells);
        }

        $out = '';
        if (!empty($cells)) {
            $out .= ' <div class="btn-group btn-group-sm" role="group">' . implode('', $cells) . '</div>';
        }
        if (!empty($additionalCells)) {
            $out .= ' <div class="btn-group btn-group-sm" role="group">' . implode('', $additionalCells) . '</div>';
        }
        return $out;
    }

    /**
     * Normalize a relation "uid" published by transferData, like "1|Company%201"
     *
     * @param string $string A transferData reference string, containing the uid
     * @return string The normalized uid
     */
    protected function normalizeUid($string)
    {
        $parts = explode('|', $string);
        return $parts[0];
    }

    /**
     * Initialized the hook objects for this class.
     * Each hook object has to implement the interface
     * \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface
     *
     * @throws \UnexpectedValueException
     * @return void
     */
    protected function initHookObjects()
    {
        $this->hookObjects = [];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'])) {
            $tceformsInlineHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'];
            if (is_array($tceformsInlineHook)) {
                foreach ($tceformsInlineHook as $classData) {
                    $processObject = GeneralUtility::getUserObj($classData);
                    if (!$processObject instanceof InlineElementHookInterface) {
                        throw new \UnexpectedValueException($classData . ' must implement interface ' . InlineElementHookInterface::class, 1202072000);
                    }
                    $this->hookObjects[] = $processObject;
                }
            }
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
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
