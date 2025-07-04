<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\ContextMenu\ItemProviders;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
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
     * @var Permission
     */
    protected $pagePermissions;

    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'view' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view',
            'iconIdentifier' => 'actions-view',
            'callbackAction' => 'viewRecord',
        ],
        'edit' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-open',
            'callbackAction' => 'editRecord',
        ],
        'new' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new',
            'iconIdentifier' => 'actions-plus',
            'callbackAction' => 'newRecord',
        ],
        'info' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info',
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'openInfoPopUp',
        ],
        'divider1' => [
            'type' => 'divider',
        ],
        'copy' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'copy',
        ],
        'copyRelease' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy-release',
            'callbackAction' => 'clipboardRelease',
        ],
        'cut' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut',
            'iconIdentifier' => 'actions-edit-cut',
            'callbackAction' => 'cut',
        ],
        'cutRelease' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cutrelease',
            'iconIdentifier' => 'actions-edit-cut-release',
            'callbackAction' => 'clipboardRelease',
        ],
        'pasteAfter' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteafter',
            'iconIdentifier' => 'actions-document-paste-after',
            'callbackAction' => 'pasteAfter',
        ],
        'divider2' => [
            'type' => 'divider',
        ],
        'more' => [
            'type' => 'submenu',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more',
            'iconIdentifier' => '',
            'callbackAction' => 'openSubmenu',
            'childItems' => [
                'newWizard' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_newWizard',
                    'iconIdentifier' => 'actions-plus',
                    'callbackAction' => 'newContentWizard',
                ],
            ],
        ],
        'divider3' => [
            'type' => 'divider',
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
     */
    public function canHandle(): bool
    {
        if (in_array($this->table, ['sys_file', 'pages'], true)) {
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
        $this->record = BackendUtility::getRecordWSOL($this->table, (int)$this->identifier);
        $this->initPermissions();
    }

    /**
     * Priority is set to lower then default value, in order to skip this provider if there is less generic provider available.
     */
    public function getPriority(): int
    {
        return 60;
    }

    /**
     * This provider works as a fallback if there is no provider dedicated for certain table, thus it's only kicking in when $items are empty.
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
                $canRender = $this->canBeEdited();
                break;
            case 'new':
                $canRender = $this->canBeNew();
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
        $this->pageRecord = BackendUtility::getRecord('pages', $this->record['pid']) ?? [];
        $this->pagePermissions = new Permission($this->backendUser->calcPerms($this->pageRecord));
    }

    /**
     * Returns true if a current user have access to given permission
     *
     * @see BackendUserAuthentication::doesUserHaveAccess()
     */
    protected function hasPagePermission(int $permission): bool
    {
        return $this->backendUser->isAdmin() || $this->pagePermissions->isGranted($permission);
    }

    /**
     * Additional attributes for JS
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [];
        if ($itemName === 'view') {
            $attributes += $this->getViewAdditionalAttributes();
        }
        if ($itemName === 'enable' || $itemName === 'disable') {
            $attributes += $this->getEnableDisableAdditionalAttributes();
        }
        if ($itemName === 'newWizard' && $this->table === 'tt_content') {
            $urlParameters = [
                'id' => $this->record['pid'],
                'sys_language_uid' => $this->record[$this->getLanguageField()] ?? null,
                'colPos' => $this->record['colPos'],
                'uid_pid' => -$this->record['uid'],
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('new_content_element_wizard', $urlParameters);
            $attributes += [
                'data-new-wizard-url' => $url,
                'data-title' => $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newContentElement'),
            ];
        }
        if ($itemName === 'delete') {
            $attributes += $this->getDeleteAdditionalAttributes();
        }
        if ($itemName === 'pasteAfter') {
            $attributes += $this->getPasteAdditionalAttributes('after');
        }
        return $attributes;
    }

    /**
     * Additional attributes for the 'view' item
     */
    protected function getViewAdditionalAttributes(): array
    {
        $attributes = [];
        $viewLink = $this->getViewLink();
        if ($viewLink) {
            $attributes += [
                'data-preview-url' => $viewLink,
            ];
        }
        return $attributes;
    }

    /**
     * Additional attributes for the hide & unhide items
     */
    protected function getEnableDisableAdditionalAttributes(): array
    {
        return [
            'data-disable-field' => $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'] ?? '',
        ];
    }

    /**
     * Additional attributes for the pasteInto and pasteAfter items
     *
     * @param string $type "after" or "into"
     */
    protected function getPasteAdditionalAttributes(string $type): array
    {
        $closeText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel');
        $okText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:ok');
        $attributes = [];
        if ($this->backendUser->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE)) {
            $selItem = $this->clipboard->getSelectedRecord();
            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste');

            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.'
                    . ($this->clipboard->currentMode() === 'copy' ? 'copy' : 'move') . '_' . $type),
                GeneralUtility::fixed_lgd_cs($selItem['_RECORD_TITLE'], (int)$this->backendUser->uc['titleLen']),
                GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($this->table, $this->record), (int)$this->backendUser->uc['titleLen'])
            );
            $attributes += [
                'data-title' => $title,
                'data-message' => $confirmMessage,
                'data-button-close-text' => $closeText,
                'data-button-ok-text' => $okText,
            ];
        }
        return $attributes;
    }

    /**
     * Additional data for a "delete" action (confirmation modal title and message)
     */
    protected function getDeleteAdditionalAttributes(): array
    {
        $closeText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel');
        $okText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:delete');
        $attributes = [];
        if ($this->backendUser->jsConfirmation(JsConfirmation::DELETE)) {
            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete.title');

            $recordInfo = GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($this->table, $this->record), (int)$this->backendUser->uc['titleLen']);
            if ($this->backendUser->shallDisplayDebugInformation()) {
                $recordInfo .= ' [' . $this->table . ':' . $this->record['uid'] . ']';
            }
            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'),
                trim($recordInfo)
            );
            $confirmMessage .= BackendUtility::referenceCount(
                $this->table,
                $this->record['uid'],
                LF . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord')
            );
            $confirmMessage .= BackendUtility::translationCount(
                $this->table,
                $this->record['uid'],
                LF . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
            );

            $attributes += [
                'data-title' => $title,
                'data-message' => $confirmMessage,
                'data-button-close-text' => $closeText,
                'data-button-ok-text' => $okText,
            ];
        }
        return $attributes;
    }

    /**
     * Returns id of the Page used for preview
     */
    protected function getPreviewPid(): int
    {
        return (int)$this->record['pid'];
    }

    /**
     * Returns the view link
     */
    protected function getViewLink(): string
    {
        return (string)PreviewUriBuilder::createForRecordPreview(
            $this->table,
            $this->record,
            $this->pageRecord['uid'] ?? 0
        )->buildUri();
    }

    /**
     * Checks if the page is allowed to show info
     */
    protected function canShowInfo(): bool
    {
        return true;
    }

    /**
     * Checks if the page is allowed to show info
     */
    protected function canShowHistory(): bool
    {
        $userTsConfig = $this->backendUser->getTSConfig();
        return (bool)trim($userTsConfig['options.']['showHistory.'][$this->table] ?? $userTsConfig['options.']['showHistory'] ?? '1');
    }

    /**
     * Checks if the record can be previewed in frontend
     */
    protected function canBeViewed(): bool
    {
        return $this->previewLinkCanBeBuild();
    }

    /**
     * Whether a record can be edited
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
            && $this->hasPagePermission(Permission::CONTENT_EDIT)
            && $this->backendUser->recordEditAccessInternals($this->table, $this->record);
        return $access;
    }

    /**
     * Whether a record can be created
     */
    protected function canBeNew(): bool
    {
        return $this->canBeEdited() && !$this->isRecordATranslation();
    }

    /**
     * Checks if disableDelete flag is set in TSConfig for the current table
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
     * Checks if the user has the right to delete the record
     */
    protected function canBeDeleted(): bool
    {
        return !$this->isDeletionDisabledInTS()
            && !$this->isRecordCurrentBackendUser()
            && $this->canBeEdited();
    }

    /**
     * Returns true if current record can be unhidden/enabled
     */
    protected function canBeEnabled(): bool
    {
        return $this->hasDisableColumnWithValue(1) && $this->canBeEdited();
    }

    /**
     * Returns true if current record can be hidden
     */
    protected function canBeDisabled(): bool
    {
        return $this->hasDisableColumnWithValue(0)
            && !$this->isRecordCurrentBackendUser()
            && $this->canBeEdited();
    }

    /**
     * Returns true new content element wizard can be shown
     */
    protected function canOpenNewCEWizard(): bool
    {
        return $this->table === 'tt_content' && $this->canBeEdited() && !$this->isRecordATranslation();
    }

    protected function canBeCopied(): bool
    {
        return !$this->isRecordInClipboard('copy')
            && !$this->isRecordATranslation();
    }

    protected function canBeCut(): bool
    {
        return !$this->isRecordInClipboard('cut')
            && $this->canBeEdited()
            && !$this->isRecordATranslation();
    }

    /**
     * Paste after is only shown for records from the same table (comparing record in clipboard and record clicked)
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
                return (int)($this->record[$hiddenFieldName] ?? 0) === (int)$value;
            }
        }
        return false;
    }

    /**
     * Record is locked if page is locked or page is not locked but record is
     */
    protected function isRecordLocked(): bool
    {
        return (int)$this->pageRecord['editlock'] === 1
            || isset($GLOBALS['TCA'][$this->table]['ctrl']['editlock'])
            && (int)$this->record[$GLOBALS['TCA'][$this->table]['ctrl']['editlock']] === 1;
    }

    /**
     * Returns true is a current record is a delete placeholder
     */
    protected function isDeletePlaceholder(): bool
    {
        return VersionState::tryFrom($this->record['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER;
    }

    /**
     * Checks if current record is in the "normal" pad of the clipboard
     *
     * @param string $mode "copy", "cut" or '' for any mode
     */
    protected function isRecordInClipboard(string $mode = ''): bool
    {
        $isSelected = '';
        if ($this->clipboard->current === 'normal' && isset($this->record['uid'])) {
            $isSelected = $this->clipboard->isSelected($this->table, $this->record['uid']);
        }
        return $mode === '' ? !empty($isSelected) : $isSelected === $mode;
    }

    /**
     * Returns true is a record ia a translation
     */
    protected function isRecordATranslation(): bool
    {
        return BackendUtility::isTableLocalizable($this->table) && (int)$this->record[$GLOBALS['TCA'][$this->table]['ctrl']['transOrigPointerField']] !== 0;
    }

    /**
     * Return true in case the current record is the current backend user
     */
    protected function isRecordCurrentBackendUser(): bool
    {
        return $this->table === 'be_users' && (int)($this->record['uid'] ?? 0) === $this->backendUser->getUserId();
    }

    protected function getIdentifier(): string
    {
        return $this->record['uid'];
    }

    /**
     * Returns true if a view link can be build for the record
     */
    protected function previewLinkCanBeBuild(): bool
    {
        return $this->getViewLink() !== '';
    }

    /**
     * Returns the configured language field
     */
    protected function getLanguageField(): string
    {
        return $GLOBALS['TCA'][$this->table]['ctrl']['languageField'] ?? '';
    }
}
