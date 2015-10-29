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
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\RelationHandler;
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
    protected $inlineData = array();

    /**
     * @var InlineStackProcessor
     */
    protected $inlineStackProcessor;

    /**
     * Array containing instances of hook classes called once for IRRE objects
     *
     * @var array
     */
    protected $hookObjects = array();

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
    }

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws AccessDeniedContentEditException
     */
    public function render()
    {
        $this->inlineData = $this->data['inlineData'];

        /** @var InlineStackProcessor $inlineStackProcessor */
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $this->inlineStackProcessor = $inlineStackProcessor;
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

        $this->initHookObjects();

        $parentUid = $this->data['inlineParentUid'];
        $record = $this->data['databaseRow'];
        $config = $this->data['inlineParentConfig'];

        $foreign_table = $config['foreign_table'];
        $foreign_selector = $config['foreign_selector'];
        $resultArray = $this->initializeResultArray();

        // Send a mapping information to the browser via JSON:
        // e.g. data[<curTable>][<curId>][<curField>] => data-<pid>-<parentTable>-<parentId>-<parentField>-<curTable>-<curId>-<curField>
        $formPrefix = $inlineStackProcessor->getCurrentStructureFormPrefix();
        $domObjectId = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $this->inlineData['map'][$formPrefix] = $domObjectId;

        $resultArray['inlineData'] = $this->inlineData;

        // Set this variable if we handle a brand new unsaved record:
        $isNewRecord = !MathUtility::canBeInterpretedAsInteger($record['uid']);
        // Set this variable if the only the default language record and inline child has not been localized yet
        $isDefaultLanguageRecord = $this->data['isInlineDefaultLanguageRecordInLocalizedParentContext'];
        // If there is a selector field, normalize it:
        if ($foreign_selector) {
            $valueToNormalize = $record[$foreign_selector];
            if (is_array($record[$foreign_selector])) {
                // @todo: this can be kicked again if always prepared rows are handled here
                $valueToNormalize = implode(',', $record[$foreign_selector]);
            }
            $record[$foreign_selector] = $this->normalizeUid($valueToNormalize);
        }
        // Get the current naming scheme for DOM name/id attributes:
        $appendFormFieldNames = '[' . $foreign_table . '][' . $record['uid'] . ']';
        $objectId = $domObjectId . '-' . $foreign_table . '-' . $record['uid'];
        $class = '';
        $html = '';
        $combinationHtml = '';
        if (!$isDefaultLanguageRecord) {
            // Get configuration:
            $collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
            $expandAll = isset($config['appearance']['collapseAll']) && !$config['appearance']['collapseAll'];
            $ajaxLoad = !isset($config['appearance']['ajaxLoad']) || $config['appearance']['ajaxLoad'];
            if ($isNewRecord) {
                // Show this record expanded or collapsed
                $isExpanded = $expandAll || (!$collapseAll ? 1 : 0);
            } else {
                $isExpanded = $config['renderFieldsOnly'] || !$collapseAll && $this->getExpandedCollapsedState($foreign_table, $record['uid']) || $expandAll;
            }
            // Render full content ONLY IF this is an AJAX request, a new record, the record is not collapsed or AJAX loading is explicitly turned off
            if ($isNewRecord || $isExpanded || !$ajaxLoad) {
                $combinationHtml = '';
                if (isset($this->data['combinationChild'])) {
                    $combinationChild = $this->renderCombinationChild($this->data, $appendFormFieldNames);
                    $combinationHtml = $combinationChild['html'];
                    $combinationChild['html'] = '';
                    $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $combinationChild);
                }

                $childArray = $this->renderChild($this->data);

                $html = $childArray['html'];
                $childArray['html'] = '';
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);
            } else {
                // This string is the marker for the JS-function to check if the full content has already been loaded
                $html = '<!--notloaded-->';
            }
            if ($isNewRecord) {
                // Get the top parent table
                $top = $this->inlineStackProcessor->getStructureLevel(0);
                $ucFieldName = 'uc[inlineView][' . $top['table'] . '][' . $top['uid'] . ']' . $appendFormFieldNames;
                // Set additional fields for processing for saving
                $html .= '<input type="hidden" name="data' . $appendFormFieldNames . '[pid]" value="' . $record['pid'] . '"/>';
                $html .= '<input type="hidden" name="' . $ucFieldName . '" value="' . $isExpanded . '" />';
            } else {
                // Set additional field for processing for saving
                $html .= '<input type="hidden" name="cmd' . $appendFormFieldNames . '[delete]" value="1" disabled="disabled" />';
                if (!$isExpanded
                    && !empty($GLOBALS['TCA'][$foreign_table]['ctrl']['enablecolumns']['disabled'])
                    && $ajaxLoad
                ) {
                    $checked = !empty($record['hidden']) ? ' checked="checked"' : '';
                    $html .= '<input type="checkbox" name="data' . $appendFormFieldNames . '[hidden]_0" value="1"' . $checked . ' />';
                    $html .= '<input type="input" name="data' . $appendFormFieldNames . '[hidden]" value="' . $record['hidden'] . '" />';
                }
            }
            // If this record should be shown collapsed
            $class = $isExpanded ? 'panel-visible' : 'panel-collapsed';
        }
        if ($config['renderFieldsOnly']) {
            $html = $html . $combinationHtml;
        } else {
            // Set the record container with data for output
            if ($isDefaultLanguageRecord) {
                $class .= ' t3-form-field-container-inline-placeHolder';
            }
            if (isset($record['hidden']) && (int)$record['hidden']) {
                $class .= ' t3-form-field-container-inline-hidden';
            }
            $class .= ($isNewRecord ? ' inlineIsNewRecord' : '');
            $html = '
				<div class="panel panel-default panel-condensed ' . trim($class) . '" id="' . $objectId . '_div">
					<div class="panel-heading" data-toggle="formengine-inline" id="' . $objectId . '_header" data-expandSingle="' . ($config['appearance']['expandSingle'] ? 1 : 0) . '">
						<div class="form-irre-header">
							<div class="form-irre-header-cell form-irre-header-icon">
								<span class="caret"></span>
							</div>
							' . $this->renderForeignRecordHeader($parentUid, $foreign_table, $this->data, $config, $isDefaultLanguageRecord) . '
						</div>
					</div>
					<div class="panel-collapse" id="' . $objectId . '_fields" data-expandSingle="' . ($config['appearance']['expandSingle'] ? 1 : 0) . '" data-returnURL="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' . $html . $combinationHtml . '</div>
				</div>';
        }

        $resultArray['html'] = $html;
        return $resultArray;
    }

    /**
     * Render inner child
     *
     * @param array $options
     * @return array Result array
     */
    protected function renderChild(array $options)
    {
        $domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($options['inlineFirstPid']);
        $options['tabAndInlineStack'][] = [
            'inline',
            $domObjectId . '-' . $options['tableName'] . '-' . $options['databaseRow']['uid'],
        ];
        // @todo: ugly construct ...
        $options['inlineData'] = $this->inlineData;
        $options['renderType'] = 'fullRecordContainer';
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * Render child child
     *
     * Render a table with FormEngine, that occurs on an intermediate table but should be editable directly,
     * so two tables are combined (the intermediate table with attributes and the sub-embedded table).
     * -> This is a direct embedding over two levels!
     *
     * @param array $options
     * @param string $appendFormFieldNames The [<table>][<uid>] of the parent record (the intermediate table)
     * @return array Result array
     */
    protected function renderCombinationChild(array $options, $appendFormFieldNames)
    {
        $childData = $options['combinationChild'];
        $parentConfig = $options['inlineParentConfig'];

        $resultArray = $this->initializeResultArray();

        // Display Warning FlashMessage if it is not suppressed
        if (!isset($parentConfig['appearance']['suppressCombinationWarning']) || empty($parentConfig['appearance']['suppressCombinationWarning'])) {
            $combinationWarningMessage = 'LLL:EXT:lang/locallang_core.xlf:warning.inline_use_combination';
            if (!empty($parentConfig['appearance']['overwriteCombinationWarningMessage'])) {
                $combinationWarningMessage = $parentConfig['appearance']['overwriteCombinationWarningMessage'];
            }
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL($combinationWarningMessage),
                '',
                FlashMessage::WARNING
            );
            $resultArray['html'] = $flashMessage->render();
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
     * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
     * @param string $foreign_table The foreign_table we create a header for
     * @param array $data Current data
     * @param array $config content of $PA['fieldConf']['config']
     * @param bool $isVirtualRecord
     * @return string The HTML code of the header
     */
    protected function renderForeignRecordHeader($parentUid, $foreign_table, $data, $config, $isVirtualRecord = false)
    {
        $rec = $data['databaseRow'];
        // Init:
        $domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $objectId = $domObjectId . '-' . $foreign_table . '-' . $rec['uid'];
        // We need the returnUrl of the main script when loading the fields via AJAX-call (to correct wizard code, so include it as 3rd parameter)
        // Pre-Processing:
        $isOnSymmetricSide = RelationHandler::isOnSymmetricSide($parentUid, $config, $rec);
        $hasForeignLabel = (bool)(!$isOnSymmetricSide && $config['foreign_label']);
        $hasSymmetricLabel = (bool)$isOnSymmetricSide && $config['symmetric_label'];

        // Get the record title/label for a record:
        // Try using a self-defined user function only for formatted labels
        if (isset($GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc'])) {
            $params = array(
                'table' => $foreign_table,
                'row' => $rec,
                'title' => '',
                'isOnSymmetricSide' => $isOnSymmetricSide,
                'options' => isset($GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc_options'])
                    ? $GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc_options']
                    : array(),
                'parent' => array(
                    'uid' => $parentUid,
                    'config' => $config
                )
            );
            // callUserFunction requires a third parameter, but we don't want to give $this as reference!
            $null = null;
            GeneralUtility::callUserFunction($GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc'], $params, $null);
            $recTitle = $params['title'];

            // Try using a normal self-defined user function
        } elseif (isset($GLOBALS['TCA'][$foreign_table]['ctrl']['label_userFunc'])) {
            $recTitle = $data['recordTitle'];
        } elseif ($hasForeignLabel || $hasSymmetricLabel) {
            $titleCol = $hasForeignLabel ? $config['foreign_label'] : $config['symmetric_label'];
            // Render title for everything else than group/db:
            if (isset($this->data['processedTca']['columns'][$titleCol]['config']['type'])
                && $this->data['processedTca']['columns'][$titleCol]['config']['type'] === 'group'
                && isset($this->data['processedTca']['columns'][$titleCol]['config']['internal_type'])
                && $this->data['processedTca']['columns'][$titleCol]['config']['internal_type'] === 'db'
            ) {
                $recTitle = BackendUtility::getProcessedValueExtra($foreign_table, $titleCol, $rec[$titleCol], 0, 0, false);
            } else {
                // $recTitle could be something like: "tx_table_123|...",
                $valueParts = GeneralUtility::trimExplode('|', $rec[$titleCol]);
                $itemParts = GeneralUtility::revExplode('_', $valueParts[0], 2);
                $recTemp = BackendUtility::getRecordWSOL($itemParts[0], $itemParts[1]);
                $recTitle = BackendUtility::getRecordTitle($itemParts[0], $recTemp, false);
            }
            $recTitle = BackendUtility::getRecordTitlePrep($recTitle);
            if (trim($recTitle) === '') {
                $recTitle = BackendUtility::getNoRecordTitle(true);
            }
        } else {
            $recTitle = BackendUtility::getRecordTitle($foreign_table, FormEngineUtility::databaseRowCompatibility($rec), true);
        }

        $altText = BackendUtility::getRecordIconAltText($rec, $foreign_table);

        $iconImg = '<span title="' . $altText . '" id="' . htmlspecialchars($objectId) . '_icon' . '">' . $this->iconFactory->getIconForRecord($foreign_table, $rec, Icon::SIZE_SMALL)->render() . '</span>';
        $label = '<span id="' . $objectId . '_label">' . $recTitle . '</span>';
        $ctrl = $this->renderForeignRecordHeaderControl($parentUid, $foreign_table, $data, $config, $isVirtualRecord);
        $thumbnail = false;

        // Renders a thumbnail for the header
        if (!empty($config['appearance']['headerThumbnail']['field'])) {
            $fieldValue = $rec[$config['appearance']['headerThumbnail']['field']];
            $firstElement = array_shift(GeneralUtility::trimExplode('|', array_shift(GeneralUtility::trimExplode(',', $fieldValue))));
            $fileUid = array_pop(BackendUtility::splitTable_Uid($firstElement));

            if (!empty($fileUid)) {
                $fileObject = ResourceFactory::getInstance()->getFileObject($fileUid);
                if ($fileObject && $fileObject->isMissing()) {
                    $flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($fileObject);
                    $thumbnail = $flashMessage->render();
                } elseif ($fileObject) {
                    $imageSetup = $config['appearance']['headerThumbnail'];
                    unset($imageSetup['field']);
                    if (!empty($rec['crop'])) {
                        $imageSetup['crop'] = $rec['crop'];
                    }
                    $imageSetup = array_merge(array('width' => '45', 'height' => '45c'), $imageSetup);
                    $processedImage = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageSetup);
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

        if (!empty($config['appearance']['headerThumbnail']['field']) && $thumbnail) {
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
     * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
     * @param string $foreign_table The table (foreign_table) we create control-icons for
     * @param array $data Current data
     * @param array $config (modified) TCA configuration of the field
     * @param bool $isVirtualRecord TRUE if the current record is virtual, FALSE otherwise
     * @return string The HTML code with the control-icons
     */
    protected function renderForeignRecordHeaderControl($parentUid, $foreign_table, $data, $config = array(), $isVirtualRecord = false)
    {
        $rec = $data['databaseRow'];
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        // Initialize:
        $cells = array();
        $additionalCells = array();
        $isNewItem = substr($rec['uid'], 0, 3) == 'NEW';
        $isParentExisting = MathUtility::canBeInterpretedAsInteger($parentUid);
        $tcaTableCtrl = &$GLOBALS['TCA'][$foreign_table]['ctrl'];
        $tcaTableCols = &$GLOBALS['TCA'][$foreign_table]['columns'];
        $isPagesTable = $foreign_table === 'pages';
        $isSysFileReferenceTable = $foreign_table === 'sys_file_reference';
        $isOnSymmetricSide = RelationHandler::isOnSymmetricSide($parentUid, $config, $rec);
        $enableManualSorting = $tcaTableCtrl['sortby'] || $config['MM'] || !$isOnSymmetricSide && $config['foreign_sortby'] || $isOnSymmetricSide && $config['symmetric_sortby'];
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $nameObjectFt = $nameObject . '-' . $foreign_table;
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
        $enabledControls = $config['appearance']['enabledControls'];
        // Hook: Can disable/enable single controls for specific child records:
        foreach ($this->hookObjects as $hookObj) {
            /** @var InlineElementHookInterface $hookObj */
            $hookObj->renderForeignRecordHeaderControl_preProcess($parentUid, $foreign_table, $rec, $config, $isVirtualRecord, $enabledControls);
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
                $table = $foreign_table;
            }
            $cells['info'] = '
				<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(('top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . GeneralUtility::quoteJSvalue($uid) . '); return false;')) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:showInfo', true) . '">
					' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '
				</a>';
        }
        // If the table is NOT a read-only table, then show these links:
        if (!$tcaTableCtrl['readOnly'] && !$isVirtualRecord) {
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
            if ($enabledControls['new'] && ($enableManualSorting || $tcaTableCtrl['useColumnsForDefaultValues'])) {
                if (!$isPagesTable && $calcPerms & Permission::CONTENT_EDIT || $isPagesTable && $calcPerms & Permission::PAGE_NEW) {
                    $onClick = 'return inline.createNewRecord(' . GeneralUtility::quoteJSvalue($nameObjectFt) . ',' . GeneralUtility::quoteJSvalue($rec['uid']) . ')';
                    $style = '';
                    if ($config['inline']['inlineNewButtonStyle']) {
                        $style = ' style="' . $config['inline']['inlineNewButtonStyle'] . '"';
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
                $style = $config['inline']['first'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
                $cells['sort.up'] = '
					<a class="btn btn-default sortingUp" href="#" onclick="' . htmlspecialchars($onClick) . '" ' . $style . ' title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveUp', true) . '">
						' . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '
					</a>';
                // Down
                $onClick = 'return inline.changeSorting(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ', \'-1\')';
                $style = $config['inline']['last'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
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
                    $url = BackendUtility::getModuleUrl('record_edit', array(
                        'edit[sys_file_metadata][' . (int)$recordInDatabase['uid'] . ']' => 'edit'
                    ));
                    $editOnClick = 'if (top.content.list_frame) {' .
                        'top.content.list_frame.location.href=' .
                        GeneralUtility::quoteJSvalue($url . '&returnUrl=') .
                        '+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search)' .
                        ';' .
                    '}';
                    $title = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:cm.editMetadata');
                    $cells['editmetadata'] = '
						<a class="btn btn-default" href="#" class="btn" onclick="' . htmlspecialchars($editOnClick) . '" title="' . htmlspecialchars($title) . '">
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
            if ($enabledControls['hide'] && $permsEdit && $hiddenField && $tcaTableCols[$hiddenField] && (!$tcaTableCols[$hiddenField]['exclude'] || $backendUser->check('non_exclude_fields', $foreign_table . ':' . $hiddenField))) {
                $onClick = 'return inline.enableDisableRecord(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ')';
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
            if ($enabledControls['dragdrop'] && $permsEdit && $enableManualSorting && $config['appearance']['useSortable']) {
                $additionalCells['dragdrop'] = '
					<span class="btn btn-default sortableHandle" data-id="' . htmlspecialchars($rec['uid']) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move', true) . '">
						' . $this->iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '
					</span>';
            }
        } elseif ($isVirtualRecord && $isParentExisting) {
            if ($enabledControls['localize'] && $data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
                $onClick = 'inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($nameObjectFt) . ', ' . GeneralUtility::quoteJSvalue($rec['uid']) . ');';
                $cells['localize'] = '
					<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localize', true) . '">
						' . $this->iconFactory->getIcon('actions-document-localize', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
        }
        // If the record is edit-locked by another user, we will show a little warning sign:
        if ($lockInfo = BackendUtility::isRecordLocked($foreign_table, $rec['uid'])) {
            $cells['locked'] = '
				<a class="btn btn-default" href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg']) . ');return false;">
					' . '<span title="' . htmlspecialchars($lockInfo['msg']) . '">' . $this->iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>' . '
				</a>';
        }
        // Hook: Post-processing of single controls for specific child records:
        foreach ($this->hookObjects as $hookObj) {
            $hookObj->renderForeignRecordHeaderControl_postProcess($parentUid, $foreign_table, $rec, $config, $isVirtualRecord, $cells);
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
     * Checks if a uid of a child table is in the inline view settings.
     *
     * @param string $table Name of the child table
     * @param int $uid uid of the the child record
     * @return bool TRUE=expand, FALSE=collapse
     */
    protected function getExpandedCollapsedState($table, $uid)
    {
        $inlineView = $this->data['inlineExpandCollapseStateArray'];
        // @todo Add checking/cleaning for unused tables, records, etc. to save space in uc-field
        if (isset($inlineView[$table]) && is_array($inlineView[$table])) {
            if (in_array($uid, $inlineView[$table]) !== false) {
                return true;
            }
        }
        return false;
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
        $this->hookObjects = array();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'])) {
            $tceformsInlineHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'];
            if (is_array($tceformsInlineHook)) {
                foreach ($tceformsInlineHook as $classData) {
                    $processObject = GeneralUtility::getUserObj($classData);
                    if (!$processObject instanceof InlineElementHookInterface) {
                        throw new \UnexpectedValueException('$processObject must implement interface ' . InlineElementHookInterface::class, 1202072000);
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
