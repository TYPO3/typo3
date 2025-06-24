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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context menu item provider for pages table
 */
class PageProvider extends RecordProvider
{
    /**
     * @var string
     */
    protected $table = 'pages';

    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'view' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view',
            'iconIdentifier' => 'actions-view-page',
            'callbackAction' => 'viewRecord',
        ],
        'edit' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => 'editRecord',
        ],
        'new' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.newSubpage',
            'iconIdentifier' => 'actions-page-new',
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
        'pasteInto' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteinto',
            'iconIdentifier' => 'actions-document-paste-into',
            'callbackAction' => 'pasteInto',
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
                    'iconIdentifier' => 'actions-page-new',
                    'callbackAction' => 'newPageWizard',
                ],
                'pagesSort' => [
                    'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pages_sort.xlf:title',
                    'iconIdentifier' => 'actions-page-move',
                    'callbackAction' => 'pagesSort',
                ],
                'pagesNewMultiple' => [
                    'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_pages_new.xlf:title',
                    'iconIdentifier' => 'apps-pagetree-drag-move-between',
                    'callbackAction' => 'pagesNewMultiple',
                ],
                'mountAsTreeRoot' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.tempMountPoint',
                    'iconIdentifier' => 'actions-pagetree-mountroot',
                    'callbackAction' => 'mountAsTreeRoot',
                ],
                'showInMenus' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_showInMenus',
                    'iconIdentifier' => 'actions-view',
                    'callbackAction' => 'showInMenus',
                ],
                'hideInMenus' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_hideInMenus',
                    'iconIdentifier' => 'actions-ban',
                    'callbackAction' => 'hideInMenus',
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
        'clearCache' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache',
            'iconIdentifier' => 'actions-system-cache-clear',
            'callbackAction' => 'clearCache',
        ],
    ];

    /**
     * @var bool
     */
    protected $languageAccess = false;

    /**
     * Checks if the provider can add items to the menu
     */
    public function canHandle(): bool
    {
        return $this->table === 'pages';
    }

    public function getPriority(): int
    {
        return 100;
    }

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
            case 'newWizard':
            case 'pagesNewMultiple':
                $canRender = $this->canBeCreated();
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
            case 'showInMenus':
                $canRender = $this->canBeToggled('nav_hide', 1);
                break;
            case 'hideInMenus':
                $canRender = $this->canBeToggled('nav_hide', 0);
                break;
            case 'delete':
                $canRender = $this->canBeDeleted();
                break;
            case 'history':
                $canRender = $this->canShowHistory();
                break;
            case 'pagesSort':
                $canRender = $this->canBeSorted();
                break;
            case 'mountAsTreeRoot':
                $canRender = !$this->isRoot();
                break;
            case 'copy':
                $canRender = $this->canBeCopied();
                break;
            case 'copyRelease':
                $canRender = $this->isRecordInClipboard('copy');
                break;
            case 'cut':
                $canRender = $this->canBeCut() && !$this->isRecordInClipboard('cut');
                break;
            case 'cutRelease':
                $canRender = $this->isRecordInClipboard('cut');
                break;
            case 'pasteAfter':
                $canRender = $this->canBePastedAfter();
                break;
            case 'pasteInto':
                $canRender = $this->canBePastedInto();
                break;
            case 'clearCache':
                $canRender = $this->canClearCache();
                break;
        }
        return $canRender;
    }

    /**
     * Saves calculated permissions for a page to speed things up
     */
    protected function initPermissions()
    {
        $this->pagePermissions = new Permission($this->backendUser->calcPerms($this->record));
        $this->languageAccess = $this->hasLanguageAccess();
    }

    /**
     * Checks if the user may create pages below the given page
     */
    protected function canBeCreated(): bool
    {
        if (!$this->backendUser->checkLanguageAccess(0)) {
            return false;
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['languageField'])
            && !in_array($this->record[$GLOBALS['TCA'][$this->table]['ctrl']['languageField']] ?? false, [0, -1])
        ) {
            return false;
        }
        if (!$this->backendUser->check('tables_modify', $this->table)) {
            return false;
        }
        return $this->hasPagePermission(Permission::PAGE_NEW);
    }

    /**
     * Checks if the user has editing rights
     */
    protected function canBeEdited(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        if ($this->isRoot()) {
            return false;
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['readOnly']) && $GLOBALS['TCA'][$this->table]['ctrl']['readOnly']) {
            return false;
        }
        if ($this->backendUser->isAdmin()) {
            return true;
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['adminOnly']) && $GLOBALS['TCA'][$this->table]['ctrl']['adminOnly']) {
            return false;
        }
        if (!$this->backendUser->check('tables_modify', $this->table)) {
            return false;
        }
        return !$this->isRecordLocked() && $this->hasPagePermission(Permission::PAGE_EDIT);
    }

    /**
     * Check if a page is locked
     */
    protected function isRecordLocked(): bool
    {
        return (bool)$this->record['editlock'];
    }

    /**
     * Checks if the page is allowed to can be cut
     */
    protected function canBeCut(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['languageField'])
            && !in_array($this->record[$GLOBALS['TCA'][$this->table]['ctrl']['languageField']] ?? false, [0, -1])
        ) {
            return false;
        }
        if (!$this->backendUser->check('tables_modify', $this->table)) {
            return false;
        }
        return !$this->isWebMount()
            && $this->canBeEdited()
            && !$this->isDeletePlaceholder();
    }

    /**
     * Checks if the page is allowed to be copied
     */
    protected function canBeCopied(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['languageField'])
            && !in_array($this->record[$GLOBALS['TCA'][$this->table]['ctrl']['languageField']] ?? false, [0, -1])
        ) {
            return false;
        }
        if (!$this->backendUser->check('tables_select', $this->table)) {
            return false;
        }
        return !$this->isRoot()
            && !$this->isWebMount()
            && !$this->isRecordInClipboard('copy')
            && $this->hasPagePermission(Permission::PAGE_SHOW)
            && !$this->isDeletePlaceholder();
    }

    /**
     * Checks if something can be pasted into the node
     */
    protected function canBePastedInto(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        $clipboardElementCount = count($this->clipboard->elFromTable($this->table));

        return $clipboardElementCount
            && $this->canBeCreated()
            && !$this->isDeletePlaceholder();
    }

    /**
     * Checks if something can be pasted after the node
     */
    protected function canBePastedAfter(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        $clipboardElementCount = count($this->clipboard->elFromTable($this->table));
        return $clipboardElementCount
            && $this->canBeCreated()
            && !$this->isDeletePlaceholder();
    }

    /**
     * Check if sub pages of given page can be sorted
     */
    protected function canBeSorted(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        return $this->backendUser->check('tables_modify', $this->table)
            && $this->hasPagePermission(Permission::CONTENT_EDIT)
            && !$this->isDeletePlaceholder()
            && $this->backendUser->workspace === 0;
    }

    /**
     * Checks if the page is allowed to be removed
     */
    protected function canBeDeleted(): bool
    {
        if (!$this->languageAccess) {
            return false;
        }
        return !$this->isRoot()
            && !$this->isDeletePlaceholder()
            && !$this->isRecordLocked()
            && !$this->isDeletionDisabledInTS()
            && $this->hasPagePermission(Permission::PAGE_DELETE);
    }

    /**
     * Checks if the page is allowed to be viewed in frontend
     */
    protected function canBeViewed(): bool
    {
        return !$this->isRoot()
            && !$this->isDeleted()
            && !$this->isExcludedDoktype()
            && $this->previewLinkCanBeBuild();
    }

    /**
     * Checks if the page is allowed to show info
     */
    protected function canShowInfo(): bool
    {
        return !$this->isRoot();
    }

    /**
     * Checks if the user has clear cache rights
     */
    protected function canClearCache(): bool
    {
        return !$this->isRoot()
            && ($this->backendUser->isAdmin() || ($this->backendUser->getTSConfig()['options.']['clearCache.']['pages'] ?? false));
    }

    /**
     * Determines whether this node is deleted.
     */
    protected function isDeleted(): bool
    {
        return !empty($this->record['deleted']) || $this->isDeletePlaceholder();
    }

    /**
     * Returns true if current record is a root page
     *
     * @return bool
     */
    protected function isRoot()
    {
        return (int)$this->identifier === 0;
    }

    /**
     * Returns true if current record is a web mount
     *
     * @return bool
     */
    protected function isWebMount()
    {
        return in_array($this->identifier, $this->backendUser->getWebmounts());
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [];
        if ($itemName === 'view') {
            $attributes += $this->getViewAdditionalAttributes();
        }
        if ($itemName === 'enable' || $itemName === 'disable') {
            $attributes += $this->getEnableDisableAdditionalAttributes();
        }
        if ($itemName === 'delete') {
            $attributes += $this->getDeleteAdditionalAttributes();
        }
        if ($itemName === 'pasteInto') {
            $attributes += $this->getPasteAdditionalAttributes('into');
        }
        if ($itemName === 'pasteAfter') {
            $attributes += $this->getPasteAdditionalAttributes('after');
        }
        if ($itemName === 'pagesSort') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $attributes += [
                'data-pages-sort-url' => (string)$uriBuilder->buildUriFromRoute('pages_sort', ['id' => $this->record['uid'] ?? null]),
            ];
        }
        if ($itemName === 'newWizard') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $attributes += [
                'data-pages-new-wizard-url' => (string)$uriBuilder->buildUriFromRoute('db_new_pages', ['id' => $this->record['uid'] ?? 0]),
            ];
        }
        if ($itemName === 'pagesNewMultiple') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $attributes += [
                'data-pages-new-multiple-url' => (string)$uriBuilder->buildUriFromRoute('pages_new', ['id' => $this->record['uid'] ?? 0]),
            ];
        }
        if ($itemName === 'edit') {
            $attributes = [
                'data-pages-language-uid' => $this->record[$this->getLanguageField()] ?? null,
            ];
        }
        return $attributes;
    }

    protected function getPreviewPid(): int
    {
        return (int)($this->record[$this->getLanguageField()] ?? 0) === 0 ? (int)$this->record['uid'] : (int)$this->record['l10n_parent'];
    }

    /**
     * Returns the view link
     */
    protected function getViewLink(): string
    {
        return (string)PreviewUriBuilder::create($this->record)->buildUri();
    }

    /**
     * Checks if user has access to this column, the doktype
     * is not excluded and that it contains the given value.
     */
    protected function canBeToggled(string $fieldName, int $value): bool
    {
        if (!$this->languageAccess || $this->isRoot()) {
            return false;
        }
        if (!empty($GLOBALS['TCA'][$this->table]['columns'][$fieldName]['exclude'])
            && !$this->isExcludedDoktype()
            && $this->backendUser->check('non_exclude_fields', $this->table . ':' . $fieldName)
            && $this->backendUser->check('tables_modify', $this->table)
        ) {
            return (int)$this->record[$fieldName] === $value;
        }
        return false;
    }

    /**
     * Returns true if a current user has access to the language of the record
     *
     * @see BackendUserAuthentication::checkLanguageAccess()
     */
    protected function hasLanguageAccess(): bool
    {
        if ($this->backendUser->isAdmin()) {
            return true;
        }
        if (($languageField = $this->getLanguageField()) !== '' && isset($this->record[$languageField])) {
            return $this->backendUser->checkLanguageAccess((int)$this->record[$languageField]);
        }
        return true;
    }

    /**
     * Returns true if the page doktype is excluded
     */
    protected function isExcludedDoktype(): bool
    {
        $excludeDoktypes = [
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_SPACER,
        ];

        return in_array((int)($this->record['doktype'] ?? 0), $excludeDoktypes, true);
    }
}
