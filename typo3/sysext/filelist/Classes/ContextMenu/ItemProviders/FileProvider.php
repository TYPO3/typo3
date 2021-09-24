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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new',
            'iconIdentifier' => 'actions-document-new',
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

    /**
     * @return bool
     */
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

            //just for folders
            case 'upload':
            case 'new':
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

    /**
     * @return bool
     */
    protected function canBeEdited(): bool
    {
        return $this->isFile()
           && $this->record->checkActionPermission('write')
           && $this->record->isTextFile();
    }

    /**
     * @return bool
     */
    protected function canEditMetadata(): bool
    {
        return $this->isFile()
           && $this->record->isIndexed()
           && $this->record->checkActionPermission('editMeta')
           && $this->record->getMetaData()->offsetExists('uid')
           && $this->backendUser->check('tables_modify', 'sys_file_metadata');
    }

    /**
     * @return bool
     */
    protected function canBeRenamed(): bool
    {
        return $this->record->checkActionPermission('rename');
    }

    /**
     * @return bool
     */
    protected function canBeDeleted(): bool
    {
        return $this->record->checkActionPermission('delete');
    }

    /**
     * @return bool
     */
    protected function canShowInfo(): bool
    {
        return $this->isFile();
    }

    /**
     * @return bool
     */
    protected function canCreateNew(): bool
    {
        return $this->isFolder() && $this->record->checkActionPermission('write');
    }

    /**
     * New filemounts can only be created for readable folders by admins
     *
     * @return bool
     */
    protected function canCreateNewFilemount(): bool
    {
        return $this->isFolder() && $this->record->checkActionPermission('read') && $this->backendUser->isAdmin();
    }

    /**
     * @return bool
     */
    protected function canBeCopied(): bool
    {
        return $this->record->checkActionPermission('read') && $this->record->checkActionPermission('copy') && !$this->isRecordInClipboard('copy');
    }

    /**
     * @return bool
     */
    protected function canBeCut(): bool
    {
        return $this->record->checkActionPermission('move') && !$this->isRecordInClipboard('cut');
    }

    /**
     * @return bool
     */
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
     * @return bool
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
     * @return bool
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

    /**
     * @return bool
     */
    protected function isStorageRoot(): bool
    {
        return $this->record->getIdentifier() === $this->record->getStorage()->getRootLevelFolder()->getIdentifier();
    }

    /**
     * @return bool
     */
    protected function isFile(): bool
    {
        return $this->record instanceof File;
    }

    /**
     * @return bool
     */
    protected function isFolder(): bool
    {
        return $this->record instanceof Folder;
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [
            'data-callback-module' => 'TYPO3/CMS/Filelist/ContextMenuActions',
        ];
        if ($itemName === 'delete' && $this->backendUser->jsConfirmation(JsConfirmation::DELETE)) {
            $recordTitle = GeneralUtility::fixed_lgd_cs($this->record->getName(), $this->backendUser->uc['titleLen']);

            $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:delete');
            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'),
                $recordTitle
            );
            if ($this->isFolder()) {
                $confirmMessage .= BackendUtility::referenceCount(
                    '_FILE',
                    $this->record->getIdentifier(),
                    ' ' . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFolder')
                );
            } else {
                $confirmMessage .= BackendUtility::referenceCount(
                    'sys_file',
                    (string)$this->record->getUid(),
                    ' ' . $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile')
                );
            }
            $closeText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:button.cancel');
            $deleteText = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:button.delete');
            $attributes += [
                'data-title' => htmlspecialchars($title),
                'data-message' => htmlspecialchars($confirmMessage),
                'data-button-close-text' => htmlspecialchars($closeText),
                'data-button-ok-text' => htmlspecialchars($deleteText),
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
                'data-title' => htmlspecialchars($title),
                'data-message' => htmlspecialchars($confirmMessage),
                'data-button-close-text' => htmlspecialchars($closeText),
                'data-button-ok-text' => htmlspecialchars($okText),
            ];
        }
        if ($itemName === 'editMetadata') {
            $attributes += [
                'data-metadata-uid' => htmlspecialchars((string)$this->record->getMetaData()->offsetGet('uid')),
            ];
        }
        if ($itemName === 'downloadFile') {
            $attributes += [
                'data-url' => htmlspecialchars((string)$this->record->getPublicUrl()),
                'data-name' => htmlspecialchars($this->record->getName()),
            ];
        }

        // Add action url for file operations
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        switch ($itemName) {
            case 'downloadFolder':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('file_download'));
                break;
            case 'rename':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('file_rename'));
                break;
            case 'edit':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('file_edit'));
                break;
            case 'upload':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('file_upload'));
                break;
            case 'new':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('file_newfolder'));
                break;
        }

        return $attributes;
    }

    /**
     * @return string
     */
    protected function getIdentifier(): string
    {
        return $this->record->getCombinedIdentifier();
    }
}
