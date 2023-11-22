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

namespace TYPO3\CMS\Filelist\ContextMenu\ItemProviders;

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\ElementBrowser\CreateFolderBrowser;

/**
 * Provides click menu items for files and folders
 */
class FileProvider extends AbstractProvider
{
    /**
     * @var File|Folder|null
     */
    protected $record;

    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'edit' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => 'editFile',
        ],
        'editMetadata' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata',
            'iconIdentifier' => 'actions-open',
            'callbackAction' => 'editMetadata',
        ],
        'rename' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.rename',
            'iconIdentifier' => 'actions-edit-rename',
            'callbackAction' => 'renameFile',
        ],
        'upload' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload',
            'iconIdentifier' => 'actions-edit-upload',
            'callbackAction' => 'uploadFile',
        ],
        'new' => [
            'label' => 'LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:actions.create_folder',
            'iconIdentifier' => 'actions-folder-add',
            'callbackAction' => 'createFolder',
        ],
        'newFile' => [
            'label' => 'LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:actions.create_file',
            'iconIdentifier' => 'actions-file-add',
            'callbackAction' => 'createFile',
        ],
        'downloadFile' => [
            'label' => 'LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:download',
            'iconIdentifier' => 'actions-download',
            'callbackAction' => 'downloadFile',
        ],
        'downloadFolder' => [
            'label' => 'LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:download',
            'iconIdentifier' => 'actions-download',
            'callbackAction' => 'downloadFolder',
        ],
        'newFileMount' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.newFilemount',
            'iconIdentifier' => 'mimetypes-x-sys_filemounts',
            'callbackAction' => 'createFilemount',
        ],
        'info' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info',
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'openInfoPopUp',
        ],
        'divider' => [
            'type' => 'divider',
        ],
        'copy' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'copyFile',
        ],
        'copyRelease' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy-release',
            'callbackAction' => 'copyReleaseFile',
        ],
        'cut' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut',
            'iconIdentifier' => 'actions-edit-cut',
            'callbackAction' => 'cutFile',
        ],
        'cutRelease' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cutrelease',
            'iconIdentifier' => 'actions-edit-cut-release',
            'callbackAction' => 'cutReleaseFile',
        ],
        'pasteInto' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteinto',
            'iconIdentifier' => 'actions-document-paste-into',
            'callbackAction' => 'pasteFileInto',
        ],
        'divider2' => [
            'type' => 'divider',
        ],
        'delete' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete',
            'iconIdentifier' => 'actions-edit-delete',
            'callbackAction' => 'deleteFile',
        ],
    ];

    public function canHandle(): bool
    {
        return $this->table === 'sys_file';
    }

    /**
     * Initialize file object
     */
    protected function initialize()
    {
        parent::initialize();
        try {
            $this->record = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->identifier);
        } catch (ResourceDoesNotExistException $e) {
            $this->record = null;
        }
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
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
            //just for files
            case 'edit':
                $canRender = $this->canBeEdited();
                break;
            case 'editMetadata':
                $canRender = $this->canEditMetadata();
                break;
            case 'info':
                $canRender = $this->canShowInfo();
                break;

                // just for folders
            case 'new':
            case 'newFile':
            case 'upload':
                $canRender = $this->canCreateNew();
                break;
            case 'newFileMount':
                $canRender = $this->canCreateNewFilemount();
                break;
            case 'pasteInto':
                $canRender = $this->canBePastedInto();
                break;

                //for both files and folders
            case 'rename':
                $canRender = $this->canBeRenamed();
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
            case 'downloadFile':
                $canRender = $this->isFile() && $this->canBeDownloaded();
                break;
            case 'downloadFolder':
                $canRender = $this->isFolder() && $this->canBeDownloaded();
                break;
            case 'delete':
                $canRender = $this->canBeDeleted();
                break;
        }
        return $canRender;
    }

    protected function canBeEdited(): bool
    {
        return $this->isFile()
           && $this->record->checkActionPermission('write')
           && $this->record->isTextFile();
    }

    protected function canEditMetadata(): bool
    {
        return $this->isFile()
           && $this->record->isIndexed()
           && $this->record->checkActionPermission('editMeta')
           && $this->record->getMetaData()->offsetExists('uid')
           && $this->backendUser->check('tables_modify', 'sys_file_metadata');
    }

    protected function canBeRenamed(): bool
    {
        return $this->record->checkActionPermission('rename');
    }

    protected function canBeDeleted(): bool
    {
        return $this->record->checkActionPermission('delete');
    }

    protected function canShowInfo(): bool
    {
        return $this->isFile();
    }

    protected function canCreateNew(): bool
    {
        return $this->isFolder() && $this->record->checkActionPermission('write');
    }

    /**
     * New filemounts can only be created for readable folders by admins
     */
    protected function canCreateNewFilemount(): bool
    {
        return $this->isFolder() && $this->record->checkActionPermission('read') && $this->backendUser->isAdmin();
    }

    protected function canBeCopied(): bool
    {
        return $this->record->checkActionPermission('read') && $this->record->checkActionPermission('copy') && !$this->isRecordInClipboard('copy');
    }

    protected function canBeCut(): bool
    {
        return $this->record->checkActionPermission('move') && !$this->isRecordInClipboard('cut');
    }

    protected function canBePastedInto(): bool
    {
        $elArr = $this->clipboard->elFromTable('_FILE');
        if (empty($elArr)) {
            return false;
        }
        $selItem = reset($elArr);
        $fileOrFolderInClipBoard = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($selItem);

        return $this->isFolder()
            && $this->record->checkActionPermission('write')
            && (
                !$fileOrFolderInClipBoard instanceof Folder
                || !$fileOrFolderInClipBoard->getStorage()->isWithinFolder($fileOrFolderInClipBoard, $this->record)
            )
            && $this->isFoldersAreInTheSameRoot($fileOrFolderInClipBoard);
    }

    protected function canBeDownloaded(): bool
    {
        if (!$this->record->checkActionPermission('read')) {
            // Early return if no read access
            return false;
        }

        $fileDownloadConfiguration = (array)($this->backendUser->getTSConfig()['options.']['file_list.']['fileDownload.'] ?? []);
        if (!($fileDownloadConfiguration['enabled'] ?? true)) {
            // File download is disabled
            return false;
        }

        if ($fileDownloadConfiguration === [] || $this->isFolder()) {
            // In case no configuration exists, or we deal with a folder, download is allowed at this point
            return true;
        }

        // Initialize file extension filter
        $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $filter->setAllowedFileExtensions(
            GeneralUtility::trimExplode(',', (string)($fileDownloadConfiguration['allowedFileExtensions'] ?? ''), true)
        );
        $filter->setDisallowedFileExtensions(
            GeneralUtility::trimExplode(',', (string)($fileDownloadConfiguration['disallowedFileExtensions'] ?? ''), true)
        );
        return $filter->isAllowed($this->record->getExtension());
    }

    /**
     * Checks if folder and record are in the same filemount
     * Cannot copy folders between filemounts
     *
     * @param File|Folder|null $fileOrFolderInClipBoard
     */
    protected function isFoldersAreInTheSameRoot($fileOrFolderInClipBoard): bool
    {
        return (!$fileOrFolderInClipBoard instanceof Folder)
            || (
                $this->record->getStorage()->getRootLevelFolder()->getCombinedIdentifier()
                == $fileOrFolderInClipBoard->getStorage()->getRootLevelFolder()->getCombinedIdentifier()
            );
    }

    /**
     * Checks if a file record is in the "normal" pad of the clipboard
     *
     * @param string $mode "copy", "cut" or '' for any mode
     */
    protected function isRecordInClipboard(string $mode = ''): bool
    {
        if ($mode !== '' && !$this->record->checkActionPermission($mode)) {
            return false;
        }
        $isSelected = '';
        // Pseudo table name for use in the clipboard.
        $table = '_FILE';
        $uid = md5($this->record->getCombinedIdentifier());
        if ($this->clipboard->current === 'normal') {
            $isSelected = $this->clipboard->isSelected($table, $uid);
        }
        return $mode === '' ? !empty($isSelected) : $isSelected === $mode;
    }

    protected function isStorageRoot(): bool
    {
        return $this->record->getIdentifier() === $this->record->getStorage()->getRootLevelFolder()->getIdentifier();
    }

    protected function isFile(): bool
    {
        return $this->record instanceof File;
    }

    protected function isFolder(): bool
    {
        return $this->record instanceof Folder;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [
            'data-callback-module' => '@typo3/filelist/context-menu-actions',
        ];
        if ($itemName === 'delete' && $this->backendUser->jsConfirmation(JsConfirmation::DELETE)) {
            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete');
            if ($this->isFolder()) {
                $attributes += [
                    'data-button-close-text' => $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.confirm.delete_folder.no'),
                    'data-button-ok-text' => $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.confirm.delete_folder.yes'),
                ];
            }
            if ($this->isFile()) {
                $attributes += [
                    'data-button-close-text' => $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.confirm.delete_file.no'),
                    'data-button-ok-text' => $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.confirm.delete_file.yes'),
                ];
            }
            $recordInfo = GeneralUtility::fixed_lgd_cs($this->record->getName(), (int)($this->backendUser->uc['titleLen'] ?? 0));
            if ($this->isFolder()) {
                if ($this->backendUser->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [' . $this->record->getIdentifier() . ']';
                }
                $confirmMessage = sprintf(
                    $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'),
                    trim($recordInfo)
                ) . BackendUtility::referenceCount(
                    '_FILE',
                    $this->record->getIdentifier(),
                    LF . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFolder')
                );
            } else {
                if ($this->backendUser->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [sys_file:' . $this->record->getUid() . ']';
                }
                $confirmMessage = sprintf(
                    $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'),
                    trim($recordInfo)
                ) . BackendUtility::referenceCount(
                    'sys_file',
                    (string)$this->record->getUid(),
                    LF . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile')
                );
            }
            $attributes += [
                'data-title' => $title,
                'data-message' => $confirmMessage,
            ];
        }
        if ($itemName === 'new' && $this->isFolder()) {
            $attributes += [
                'data-identifier' => $this->record->getCombinedIdentifier(),
                'data-mode' => CreateFolderBrowser::IDENTIFIER,
            ];
        }
        if ($itemName === 'pasteInto' && $this->backendUser->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE)) {
            $elArr = $this->clipboard->elFromTable('_FILE');
            $selItem = reset($elArr);
            $fileOrFolderInClipBoard = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($selItem);

            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste');

            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.'
                    . ($this->clipboard->currentMode() === 'copy' ? 'copy' : 'move') . '_into'),
                $fileOrFolderInClipBoard->getName(),
                $this->record->getName()
            );
            $closeText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:button.cancel');
            $okLabel = $this->clipboard->currentMode() === 'copy' ? 'copy' : 'pasteinto';
            $okText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . $okLabel);
            $attributes += [
                'data-title' => $title,
                'data-message' => $confirmMessage,
                'data-button-close-text' => $closeText,
                'data-button-ok-text' => $okText,
            ];
        }
        if ($itemName === 'downloadFile') {
            $attributes += [
                'data-url' => (string)$this->record->getPublicUrl(),
                'data-name' => $this->record->getName(),
            ];
        }

        // Resource Settings
        $attributes['data-filecontext-type'] = $this->record instanceof File ? 'file' : 'folder';
        $attributes['data-filecontext-identifier'] = $this->getIdentifier();
        $attributes['data-filecontext-stateIdentifier'] = $this->record->getStorage()->getUid() . '_' . GeneralUtility::md5int($this->record->getIdentifier());
        $attributes['data-filecontext-name'] = $this->record->getName();
        $attributes['data-filecontext-uid'] = $this->record instanceof File ? $this->record->getUid() : '';
        $attributes['data-filecontext-meta-uid'] = $this->record instanceof File ? $this->record->getMetaData()->offsetGet('uid') : '';

        // Add action url for file operations
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        switch ($itemName) {
            case 'downloadFolder':
                $attributes['data-action-url'] = (string)$uriBuilder->buildUriFromRoute('file_download');
                break;
            case 'edit':
                $attributes['data-action-url'] = (string)$uriBuilder->buildUriFromRoute('file_edit');
                break;
            case 'upload':
                $attributes['data-action-url'] = (string)$uriBuilder->buildUriFromRoute('file_upload');
                break;
            case 'new':
                $attributes['data-action-url'] = (string)$uriBuilder->buildUriFromRoute('wizard_element_browser');
                break;
            case 'newFile':
                $attributes['data-action-url'] = (string)$uriBuilder->buildUriFromRoute('file_create');
                break;
        }

        return $attributes;
    }

    protected function getIdentifier(): string
    {
        return $this->record->getCombinedIdentifier();
    }
}
