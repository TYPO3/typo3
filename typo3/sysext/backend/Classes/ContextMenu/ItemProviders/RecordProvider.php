<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\ContextMenu\ItemProviders;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Class responsible for providing click menu items for db records which don't have custom provider (as e.g. pages)
 */
class RecordProvider extends AbstractProvider
{
    /**
     * Database record
     *
     * @var array
     */
    protected $record = [];

    /**
     * Database record of the page $this->record is placed on
     *
     * @var array
     */
    protected $pageRecord = [];

    /**
     * Local cache for the result of BackendUserAuthentication::calcPerms()
     *
     * @var int
     */
    protected $pagePermissions = 0;

    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'view' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view',
            'iconIdentifier' => 'actions-view',
            'callbackAction' => 'viewRecord'
        ],
        'edit' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-open',
            'callbackAction' => 'editRecord'
        ],
        'new' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new',
            'iconIdentifier' => 'actions-add',
            'callbackAction' => 'newRecord'
        ],
        'info' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info',
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'openInfoPopUp'
        ],
        'divider1' => [
            'type' => 'divider'
        ],
        'copy' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'copy'
        ],
        'copyRelease' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy-release',
            'callbackAction' => 'clipboardRelease'
        ],
        'cut' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut',
            'iconIdentifier' => 'actions-edit-cut',
            'callbackAction' => 'cut'
        ],
        'cutRelease' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cutrelease',
            'iconIdentifier' => 'actions-edit-cut-release',
            'callbackAction' => 'clipboardRelease'
        ],
        'pasteAfter' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteafter',
            'iconIdentifier' => 'actions-document-paste-after',
            'callbackAction' => 'pasteAfter'
        ],
        'divider2' => [
            'type' => 'divider'
        ],
        'more' => [
            'type' => 'submenu',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more',
            'iconIdentifier' => '',
            'callbackAction' => 'openSubmenu',
            'childItems' => [
                'newWizard' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_newWizard',
                    'iconIdentifier' => 'actions-add',
                    'callbackAction' => 'newContentWizard',
                ],
                'openListModule' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_db_list',
                    'iconIdentifier' => 'actions-system-list-open',
                    'callbackAction' => 'openListModule',
                ],
            ],
        ],
        'divider3' => [
            'type' => 'divider'
        ],
        'enable' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:enable',
            'iconIdentifier' => 'actions-edit-unhide',
            'callbackAction' => 'enableRecord',
        ],
        'disable' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:disable',
            'iconIdentifier' => 'actions-edit-hide',
            'callbackAction' => 'disableRecord',
        ],
        'delete' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete',
            'iconIdentifier' => 'actions-edit-delete',
            'callbackAction' => 'deleteRecord',
        ],
        'history' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_history',
            'iconIdentifier' => 'actions-document-history-open',
            'callbackAction' => 'openHistoryPopUp',
        ],
    ];

    /**
     * Whether this provider should kick in
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        if (in_array($this->table, ['sys_file', 'sys_filemounts', 'sys_file_storage', 'pages'], true)
            || strpos($this->table, '-drag') !== false) {
            return false;
        }
        return isset($GLOBALS['TCA'][$this->table]);
    }

    /**
     * Initialize db record
     */
    protected function initialize()
    {
        parent::initialize();
        $this->record = BackendUtility::getRecordWSOL($this->table, $this->identifier);
        $this->initPermissions();
    }

    /**
     * Priority is set to lower then default value, in order to skip this provider if there is less generic provider available.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 60;
    }

    /**
     * This provider works as a fallback if there is no provider dedicated for certain table, thus it's only kicking in when $items are empty.
     *
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        if (!empty($items)) {
            return $items;
        }
        $this->initialize();
        return $this->prepareItems($this->itemsConfiguration);
    }

    /**
     * Whether a given item can be rendered (e.g. user has enough permissions)
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($type, ['divider', 'submenu'], true)) {
            return true;
        }
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'view':
                $canRender = $this->canBeViewed();
                break;
            case 'edit':
            case 'new':
                $canRender = $this->canBeEdited();
                break;
            case 'newWizard':
                $canRender = $this->canOpenNewCEWizard();
                break;
            case 'info':
                $canRender = $this->canShowInfo();
                break;
            case 'enable':
                $canRender = $this->canBeEnabled();
                break;
            case 'disable':
                $canRender = $this->canBeDisabled();
                break;
            case 'delete':
                $canRender = $this->canBeDeleted();
                break;
            case 'history':
                $canRender = $this->canShowHistory();
                break;
            case 'openListModule':
                $canRender = $this->canOpenListModule();
                break;
            case 'copy':
                $canRender = $this->canBeCopied();
                break;
            case 'copyRelease':
                $canRender = $this->isRecordInClipboard('copy');
                break;
            case 'cut':
                $canRender = $this->canBeCut();
                break;
            case 'cutRelease':
                $canRender = $this->isRecordInClipboard('cut');
                break;
            case 'pasteAfter':
                $canRender = $this->canBePastedAfter();
                break;
        }
        return $canRender;
    }

    /**
     * Saves calculated permissions for a page containing given record, to speed things up
     */
    protected function initPermissions()
    {
        $this->pageRecord = BackendUtility::getRecord('pages', $this->record['pid']);
        $this->pagePermissions = $this->backendUser->calcPerms($this->pageRecord);
    }

    /**
     * Returns true if a current user have access to given permission
     *
     * @see BackendUserAuthentication::doesUserHaveAccess()
     * @param int $permission
     * @return bool
     */
    protected function hasPagePermission(int $permission): bool
    {
        return $this->backendUser->isAdmin() || ($this->pagePermissions & $permission) == $permission;
    }

    /**
     * Additional attributes for JS
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [];
        if ($itemName === 'view') {
            $attributes += $this->getViewAdditionalAttributes();
        }
        if ($itemName === 'newWizard' && $this->table === 'tt_content') {
            $moduleName = BackendUtility::getPagesTSconfig($this->record['pid'])['mod.']['newContentElementWizard.']['override']
                ?? 'new_content_element_wizard';
            $urlParameters = [
                'id' => $this->record['pid'],
                'sys_language_uid' => $this->record['sys_language_uid'],
                'colPos' => $this->record['colPos'],
                'uid_pid' => -$this->record['uid']
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute($moduleName, $urlParameters);
            $attributes += [
                'data-new-wizard-url' => htmlspecialchars($url),
                'data-title' => $this->languageService->getLL('newContentElement'),
            ];
        }
        if ($itemName === 'delete') {
            $attributes += $this->getDeleteAdditionalAttributes();
        }
        if ($itemName === 'openListModule') {
            $attributes += [
                'data-page-uid' => $this->record['pid']
            ];
        }
        if ($itemName === 'pasteAfter') {
            $attributes += $this->getPasteAdditionalAttributes('after');
        }
        return $attributes;
    }

    /**
     * Additional attributes for the 'view' item
     *
     * @return array
     */
    protected function getViewAdditionalAttributes(): array
    {
        $attributes = [];
        $viewLink = $this->getViewLink();
        if ($viewLink) {
            $attributes += [
                'data-preview-url' => htmlspecialchars($viewLink),
            ];
        }
        return $attributes;
    }

    /**
     * Additional attributes for the pasteInto and pasteAfter items
     *
     * @param string $type "after" or "into"
     * @return array
     */
    protected function getPasteAdditionalAttributes(string $type): array
    {
        $attributes = [];
        if ($this->backendUser->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE)) {
            $selItem = $this->clipboard->getSelectedRecord();
            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste');

            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.'
                    . ($this->clipboard->currentMode() === 'copy' ? 'copy' : 'move') . '_' . $type),
                GeneralUtility::fixed_lgd_cs($selItem['_RECORD_TITLE'], $this->backendUser->uc['titleLen']),
                GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($this->table, $this->record), $this->backendUser->uc['titleLen'])
            );
            $attributes += [
                'data-title' => htmlspecialchars($title),
                'data-message' => htmlspecialchars($confirmMessage)
            ];
        }
        return $attributes;
    }

    /**
     * Additional data for a "delete" action (confirmation modal title and message)
     *
     * @return array
     */
    protected function getDeleteAdditionalAttributes(): array
    {
        $attributes = [];
        if ($this->backendUser->jsConfirmation(JsConfirmation::DELETE)) {
            $recordTitle = GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($this->table, $this->record), $this->backendUser->uc['titleLen']);

            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:delete');
            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'),
                $recordTitle
            );
            $confirmMessage .= BackendUtility::referenceCount(
                $this->table,
                $this->record['uid'],
                ' ' . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord')
            );
            $confirmMessage .= BackendUtility::translationCount(
                $this->table,
                $this->record['uid'],
                ' ' . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
            );
            $attributes += [
                'data-title' => htmlspecialchars($title),
                'data-message' => htmlspecialchars($confirmMessage)
            ];
        }
        return $attributes;
    }

    /**
     * Returns id of the Page used for preview
     *
     * @return int
     */
    protected function getPreviewPid(): int
    {
        return (int)$this->record['pid'];
    }

    /**
     * Returns the view link
     *
     * @return string
     */
    protected function getViewLink(): string
    {
        $anchorSection = '';
        $additionalParams = '';
        if ($this->table === 'tt_content') {
            $anchorSection = '#c' . $this->record['uid'];
            $language = (int)$this->record[$GLOBALS['TCA']['tt_content']['ctrl']['languageField']];
            if ($language > 0) {
                $additionalParams = '&L=' . $language;
            }
        }
        $javascriptLink = BackendUtility::viewOnClick(
            $this->getPreviewPid(),
            '',
            null,
            $anchorSection,
            '',
            $additionalParams
        );
        $extractedLink = '';
        if (preg_match('/window\\.open\\(\'([^\']+)\'/i', $javascriptLink, $match)) {
            // Clean JSON-serialized ampersands ('&')
            // @see GeneralUtility::quoteJSvalue()
            $extractedLink = json_decode('"' . trim($match[1], '"') . '"');
        }
        return $extractedLink;
    }

    /**
     * Checks if the page is allowed to show info
     *
     * @return bool
     */
    protected function canShowInfo(): bool
    {
        return true;
    }

    /**
     * Checks if the page is allowed to show info
     *
     * @return bool
     */
    protected function canShowHistory(): bool
    {
        $userTsConfig = $this->backendUser->getTSConfig();
        return (bool)trim($userTsConfig['options.']['showHistory.'][$this->table] ?? $userTsConfig['options.']['showHistory'] ?? '1');
    }

    /**
     * Checks if the record can be previewed in frontend
     *
     * @return bool
     */
    protected function canBeViewed(): bool
    {
        return $this->table === 'tt_content';
    }

    /**
     * Whether a record can be edited
     *
     * @return bool
     */
    protected function canBeEdited(): bool
    {
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['readOnly']) && $GLOBALS['TCA'][$this->table]['ctrl']['readOnly']) {
            return false;
        }
        if ($this->backendUser->isAdmin()) {
            return true;
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['adminOnly']) && $GLOBALS['TCA'][$this->table]['ctrl']['adminOnly']) {
            return false;
        }

        $access = !$this->isRecordLocked()
            && $this->backendUser->check('tables_modify', $this->table)
            && $this->hasPagePermission(Permission::CONTENT_EDIT);
        return $access;
    }

    /**
     * Checks if disableDelete flag is set in TSConfig for the current table
     *
     * @return bool
     */
    protected function isDeletionDisabledInTS(): bool
    {
        return (bool)\trim(
            $this->backendUser->getTSConfig()['options.']['disableDelete.'][$this->table]
            ?? $this->backendUser->getTSConfig()['options.']['disableDelete']
            ?? ''
        );
    }

    /**
     * Checks if the user has the right to delete the page
     *
     * @return bool
     */
    protected function canBeDeleted(): bool
    {
        return !$this->isDeletionDisabledInTS() && $this->canBeEdited();
    }

    /**
     * Returns true if current record can be unhidden/enabled
     *
     * @return bool
     */
    protected function canBeEnabled(): bool
    {
        return $this->hasDisableColumnWithValue(1) && $this->canBeEdited();
    }

    /**
     * Returns true if current record can be hidden
     *
     * @return bool
     */
    protected function canBeDisabled(): bool
    {
        return $this->hasDisableColumnWithValue(0) && $this->canBeEdited();
    }

    /**
     * Returns true new content element wizard can be shown
     *
     * @return bool
     */
    protected function canOpenNewCEWizard(): bool
    {
        return $this->table === 'tt_content'
            && (bool)(BackendUtility::getPagesTSconfig($this->record['pid'])['mod.']['web_layout.']['disableNewContentElementWizard'] ?? true)
            && $this->canBeEdited();
    }

    /**
     * @return bool
     */
    protected function canOpenListModule(): bool
    {
        return $this->backendUser->check('modules', 'web_list');
    }

    /**
     * @return bool
     */
    protected function canBeCopied(): bool
    {
        return !$this->isRecordInClipboard('copy')
            && !$this->isRecordATranslation();
    }

    /**
     * @return bool
     */
    protected function canBeCut(): bool
    {
        return !$this->isRecordInClipboard('cut')
            && $this->canBeEdited()
            && !$this->isRecordATranslation();
    }

    /**
     * Paste after is only shown for records from the same table (comparing record in clipboard and record clicked)
     *
     * @return bool
     */
    protected function canBePastedAfter(): bool
    {
        $clipboardElementCount = count($this->clipboard->elFromTable($this->table));

        return $clipboardElementCount
            && $this->backendUser->check('tables_modify', $this->table)
            && $this->hasPagePermission(Permission::CONTENT_EDIT);
    }

    /**
     * Checks if table have "disable" column (e.g. "hidden"), if user has access to this column
     * and if it contains given value
     *
     * @param int $value
     * @return bool
     */
    protected function hasDisableColumnWithValue(int $value): bool
    {
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'])) {
            $hiddenFieldName = $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'];
            if (
                $hiddenFieldName !== '' && !empty($GLOBALS['TCA'][$this->table]['columns'][$hiddenFieldName])
                && (
                    empty($GLOBALS['TCA'][$this->table]['columns'][$hiddenFieldName]['exclude'])
                    || $this->backendUser->check('non_exclude_fields', $this->table . ':' . $hiddenFieldName)
                )
            ) {
                return (int)$this->record[$hiddenFieldName] === (int)$value;
            }
        }
        return false;
    }

    /**
     * Record is locked if page is locked or page is not locked but record is
     *
     * @return bool
     */
    protected function isRecordLocked(): bool
    {
        return (int)$this->pageRecord['editlock'] === 1
            || isset($GLOBALS['TCA'][$this->table]['ctrl']['editlock'])
            && (int)$this->record[$GLOBALS['TCA'][$this->table]['ctrl']['editlock']] === 1;
    }

    /**
     * Returns true is a current record is a delete placeholder
     *
     * @return bool
     */
    protected function isDeletePlaceholder(): bool
    {
        if (!isset($this->record['t3ver_state'])) {
            return false;
        }
        return VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER);
    }

    /**
     * Checks if current record is in the "normal" pad of the clipboard
     *
     * @param string $mode "copy", "cut" or '' for any mode
     * @return bool
     */
    protected function isRecordInClipboard(string $mode = ''): bool
    {
        $isSelected = '';
        if ($this->clipboard->current === 'normal') {
            $isSelected = $this->clipboard->isSelected($this->table, $this->record['uid']);
        }
        return $mode === '' ? !empty($isSelected) : $isSelected === $mode;
    }

    /**
     * Returns true is a record ia a translation
     *
     * @return bool
     */
    protected function isRecordATranslation(): bool
    {
        return BackendUtility::isTableLocalizable($this->table) && (int)$this->record[$GLOBALS['TCA'][$this->table]['ctrl']['transOrigPointerField']] !== 0;
    }

    /**
     * @return string
     */
    protected function getIdentifier(): string
    {
        return $this->record['uid'];
    }
}
