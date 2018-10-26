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
            'callbackAction' => 'viewRecord'
        ],
        'edit' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => 'editRecord'
        ],
        'new' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new',
            'iconIdentifier' => 'actions-page-new',
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
        'pasteInto' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteinto',
            'iconIdentifier' => 'actions-document-paste-into',
            'callbackAction' => 'pasteInto'
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
                'openListModule' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_db_list',
                    'iconIdentifier' => 'actions-system-list-open',
                    'callbackAction' => 'openListModule',
                ],
                'mountAsTreeRoot' => [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.tempMountPoint',
                    'iconIdentifier' => 'actions-pagetree-mountroot',
                    'callbackAction' => 'mountAsTreeRoot',
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
        'clearCache' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache',
            'iconIdentifier' => 'actions-system-cache-clear',
            'callbackAction' => 'clearCache',
        ],
    ];

    /**
     * Checks if the provider can add items to the menu
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'pages';
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
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
            case 'delete':
                $canRender = $this->canBeDeleted();
                break;
            case 'history':
                $canRender = $this->canShowHistory();
                break;
            case 'openListModule':
                $canRender = $this->canOpenListModule();
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
        $this->pagePermissions = $this->backendUser->calcPerms($this->record);
    }

    /**
     * Checks if the user may create pages below the given page
     *
     * @return bool
     */
    protected function canBeCreated(): bool
    {
        return $this->hasPagePermission(Permission::PAGE_NEW);
    }

    /**
     * Checks if the user has editing rights
     *
     * @return bool
     */
    protected function canBeEdited(): bool
    {
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
        return !$this->isRecordLocked() && $this->hasPagePermission(Permission::PAGE_EDIT);
    }

    /**
     * Check if a page is locked
     *
     * @return bool
     */
    protected function isRecordLocked(): bool
    {
        return (bool)$this->record['editlock'];
    }

    /**
     * Checks if the page is allowed to can be cut
     *
     * @return bool
     */
    protected function canBeCut(): bool
    {
        return !$this->isWebMount()
            && $this->canBeEdited()
            && !$this->isDeletePlaceholder();
    }

    /**
     * Checks if the page is allowed to be copied
     *
     * @return bool
     */
    protected function canBeCopied(): bool
    {
        return !$this->isRoot()
            && !$this->isWebMount()
            && !$this->isRecordInClipboard('copy')
            && $this->hasPagePermission(Permission::PAGE_SHOW)
            && !$this->isDeletePlaceholder();
    }

    /**
     * Checks if something can be pasted into the node
     *
     * @return bool
     */
    protected function canBePastedInto(): bool
    {
        $clipboardElementCount = count($this->clipboard->elFromTable($this->table));

        return $clipboardElementCount
            && $this->canBeCreated()
            && !$this->isDeletePlaceholder();
    }

    /**
     * Checks if something can be pasted after the node
     *
     * @return bool
     */
    protected function canBePastedAfter(): bool
    {
        $clipboardElementCount = count($this->clipboard->elFromTable($this->table));
        return $clipboardElementCount
            && $this->canBeCreated()
            && !$this->isDeletePlaceholder();
    }

    /**
     * Check if sub pages of given page can be sorted
     *
     * @return bool
     */
    protected function canBeSorted(): bool
    {
        return $this->backendUser->check('tables_modify', $this->table)
            && $this->hasPagePermission(Permission::CONTENT_EDIT)
            && !$this->isDeletePlaceholder()
            && $this->backendUser->workspace === 0;
    }

    /**
     * Checks if the page is allowed to be removed
     *
     * @return bool
     */
    protected function canBeDeleted(): bool
    {
        return !$this->isDeletePlaceholder()
            && !$this->isRecordLocked()
            && !$this->isDeletionDisabledInTS()
            && $this->hasPagePermission(Permission::PAGE_DELETE);
    }

    /**
     * Checks if the page is allowed to be viewed in frontend
     *
     * @return bool
     */
    protected function canBeViewed(): bool
    {
        return !$this->isRoot() && !$this->isDeleted();
    }

    /**
     * Checks if the page is allowed to show info
     *
     * @return bool
     */
    protected function canShowInfo(): bool
    {
        return !$this->isRoot();
    }

    /**
     * Checks if the user has clear cache rights
     *
     * @return bool
     */
    protected function canClearCache(): bool
    {
        return !$this->isRoot()
            && ($this->backendUser->isAdmin() || $this->backendUser->getTSConfig()['options.']['clearCache.']['pages'] ?? false);
    }

    /**
     * Determines whether this node is deleted.
     *
     * @return bool
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
        return in_array($this->identifier, $this->backendUser->returnWebmounts());
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [];
        if ($itemName === 'view') {
            $attributes += $this->getViewAdditionalAttributes();
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
                'data-pages-sort-url' => (string)$uriBuilder->buildUriFromRoute('pages_sort', ['id' => $this->record['uid']]),
            ];
        }
        if ($itemName === 'pagesNewMultiple') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $attributes += [
                'data-pages-new-multiple-url' => (string)$uriBuilder->buildUriFromRoute('pages_new', ['id' => $this->record['uid']]),
            ];
        }
        if ($itemName === 'edit') {
            $attributes = [
                'data-pages-language-uid' => $this->record['sys_language_uid']
            ];
        }
        return $attributes;
    }

    /**
     * @return int
     */
    protected function getPreviewPid(): int
    {
        return (int)$this->record['uid'];
    }
}
