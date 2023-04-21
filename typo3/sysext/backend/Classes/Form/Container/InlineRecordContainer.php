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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent;
use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
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
     * @var IconFactory
     */
    protected $iconFactory;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Default constructor
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
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
                if (!empty($hiddenField) && (!$data['isInlineChildExpanded'] || !in_array($hiddenField, $data['columnsToProcess'], true))) {
                    $checked = !empty($record[$hiddenField]) ? ' checked="checked"' : '';
                    $html .= '<input type="checkbox" class="d-none" data-formengine-input-name="data'
                        . htmlspecialchars($appendFormFieldNames)
                        . '[' . htmlspecialchars($hiddenField) . ']" value="1"' . $checked . ' />';
                    $html .= '<input type="input" class="d-none" name="data' . htmlspecialchars($appendFormFieldNames)
                        . '[' . htmlspecialchars($hiddenField) . ']" value="' . htmlspecialchars($record[$hiddenField]) . '" />';
                }
            }
            // If this record should be shown collapsed
            $classes[] = $data['isInlineChildExpanded'] ? 'panel-visible' : 'panel-collapsed';
        }
        $hiddenFieldHtml = implode(LF, $resultArray['additionalHiddenFields'] ?? []);

        if ($inlineConfig['renderFieldsOnly'] ?? false) {
            // Render "body" part only
            $html .= $hiddenFieldHtml . $combinationHtml;
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
                'data-placeholder-record' => $data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ? '1' : '0',
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
            $markup[] = '            <span class="icon-emphasized">';
            $markup[] = '                ' . $this->iconFactory->getIcon('actions-exclamation', Icon::SIZE_SMALL)->render();
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
    protected function renderForeignRecordHeader(array $data, string $ariaAttributesString): string
    {
        $record = $data['databaseRow'];
        $recordTitle = $data['recordTitle'];
        $foreignTable = $data['inlineParentConfig']['foreign_table'];
        $domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid']);

        if (!empty($recordTitle)) {
            // The user function may return HTML, therefore we can't escape it
            if (empty($data['processedTca']['ctrl']['formattedLabel_userFunc'])) {
                $recordTitle = BackendUtility::getRecordTitlePrep($recordTitle);
            }
        } else {
            $recordTitle = '<em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</em>';
        }

        // In case the record title is not generated by a formattedLabel_userFunc, which already
        // contains custom markup, and we are in debug mode, add the inline record table name.
        if (empty($data['processedTca']['ctrl']['formattedLabel_userFunc'])
            && $this->getBackendUserAuthentication()->shallDisplayDebugInformation()
        ) {
            $recordTitle .= ' <code class="m-0">[' . htmlspecialchars($foreignTable) . ']</code>';
        }

        $objectId = htmlspecialchars($domObjectId . '-' . $foreignTable . '-' . ($record['uid'] ?? 0));
        return '
            <button class="form-irre-header-cell form-irre-header-button" ' . $ariaAttributesString . '>
                <div class="form-irre-header-icon" id="' . $objectId . '_iconcontainer">
                    ' . $this->iconFactory->getIconForRecord($foreignTable, $record, Icon::SIZE_SMALL)->setTitle(BackendUtility::getRecordIconAltText($record, $foreignTable))->render() . '
                </div>
                <div class="form-irre-header-body"><span id="' . $objectId . '_label">' . $recordTitle . '</span></div>
            </button>
            <div class="form-irre-header-cell form-irre-header-control t3js-formengine-irre-control">
                ' . $this->renderForeignRecordHeaderControl($data) . '
            </div>';
    }

    /**
     * Render the control-icons for a record header (create new, sorting, delete, disable/enable).
     * Most of the parts are copy&paste from TYPO3\CMS\Backend\RecordList\DatabaseRecordList and
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
        ];
        $inlineConfig = $data['inlineParentConfig'];
        $foreignTable = $inlineConfig['foreign_table'];
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        // Initialize:
        $cells = [
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
        $isNewItem = str_starts_with($rec['uid'], 'NEW');
        $isParentReadOnly = isset($inlineConfig['readOnly']) && $inlineConfig['readOnly'];
        $isParentExisting = MathUtility::canBeInterpretedAsInteger($data['inlineParentUid']);
        $tcaTableCtrl = $GLOBALS['TCA'][$foreignTable]['ctrl'];
        $tcaTableCols = $GLOBALS['TCA'][$foreignTable]['columns'];
        $isPagesTable = $foreignTable === 'pages';
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
        // The event contains all controls and their state (enabled / disabled), which might got modified by listeners
        $event = $this->eventDispatcher->dispatch(new ModifyInlineElementEnabledControlsEvent($data, $rec));
        if ($data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            $cells['localize'] = $this->iconFactory
                ->getIcon('actions-edit-localize-status-low', Icon::SIZE_SMALL)
                ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localize.isLocalizable'))
                ->render();
        }
        // "Info": (All records)
        if ($event->isControlEnabled('info')) {
            if ($isNewItem) {
                $cells['info'] = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
            } else {
                $cells['info'] = '
				<button type="button" class="btn btn-default" data-action="infowindow" data-info-table="' . htmlspecialchars($foreignTable) . '" data-info-uid="' . htmlspecialchars($rec['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo')) . '">
					' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '
				</button>';
            }
        }
        // If the table is NOT a read-only table, then show these links:
        if (!$isParentReadOnly && !($tcaTableCtrl['readOnly'] ?? false) && !($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false)) {
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
            if ($event->isControlEnabled('new') && ($enableManualSorting || ($tcaTableCtrl['useColumnsForDefaultValues'] ?? false))) {
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
            if ($event->isControlEnabled('sort') && $permsEdit && $enableManualSorting) {
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
            // "Delete" link:
            if ($event->isControlEnabled('delete')
                && (
                    ($isPagesTable && $localCalcPerms->deletePagePermissionIsGranted())
                    || (!$isPagesTable && $calcPerms->editContentPermissionIsGranted())
                )
            ) {
                $title = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:delete'));
                $icon = $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render();

                $recordInfo = $data['databaseRow']['uid_local'][0]['title'] ?? $data['recordTitle'] ?? '';
                if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [' . $data['tableName'] . ':' . $data['vanillaUid'] . ']';
                }

                $cells['delete'] = '
                    <button type="button" class="btn btn-default t3js-editform-delete-inline-record" data-record-info="' . htmlspecialchars(trim($recordInfo)) . '" title="' . $title . '">
                        ' . $icon . '
                    </button>';
            }

            // "Hide/Unhide" links:
            $hiddenField = $tcaTableCtrl['enablecolumns']['disabled'] ?? '';
            if ($event->isControlEnabled('hide')
                && $permsEdit
                && $hiddenField
                && ($tcaTableCols[$hiddenField] ?? false)
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
            if ($event->isControlEnabled('dragdrop') && $permsEdit && $enableManualSorting && ($inlineConfig['appearance']['useSortable'] ?? false)) {
                $cells['dragdrop'] = '
                    <span class="btn btn-default sortableHandle" data-id="' . htmlspecialchars($rec['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move')) . '">
                        ' . $this->iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '
                    </span>';
            }
        } elseif (($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false) && $isParentExisting) {
            if ($event->isControlEnabled('localize') && ($data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false)) {
                $cells['localize'] = '
                    <button type="button" class="btn btn-default t3js-synchronizelocalize-button" data-type="' . htmlspecialchars($rec['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localize')) . '">
                        ' . $this->iconFactory->getIcon('actions-document-localize', Icon::SIZE_SMALL)->render() . '
                    </button>';
            }
        }
        // If the record is edit-locked by another user, we will show a little warning sign:
        if ($lockInfo = BackendUtility::isRecordLocked($foreignTable, $rec['uid'])) {
            $cells['locked'] = '
				<button type="button" class="btn btn-default" title="' . htmlspecialchars($lockInfo['msg']) . '">
					' . $this->iconFactory->getIcon('status-user-backend', Icon::SIZE_SMALL, 'overlay-edit')->render() . '
				</button>';
        }

        // Get modified controls. This means their markup was modified, new controls were added or controls got removed.
        $cells = $this->eventDispatcher->dispatch(new ModifyInlineElementControlsEvent($cells, $data, $rec))->getControls();

        $out = '';
        if (!empty($cells['hide']) || !empty($cells['delete'])) {
            $out .= '<div class="btn-group btn-group-sm" role="group">' . $cells['hide'] . $cells['delete'] . '</div>';
            unset($cells['hide'], $cells['delete']);
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
            $cellContent = trim(implode('', $cells));
            $out .= $cellContent !== '' ? ' <div class="btn-group btn-group-sm" role="group">' . $cellContent . '</div>' : '';
        }
        return $out;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
