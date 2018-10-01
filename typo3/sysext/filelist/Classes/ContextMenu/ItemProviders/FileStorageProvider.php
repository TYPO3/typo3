<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Filelist\ContextMenu\ItemProviders;

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

/**
 * Provides click menu items for file_storage
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileStorageProvider extends FileProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'edit' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-open',
            'callbackAction' => 'editFileStorage'
        ],
        'upload' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload',
            'iconIdentifier' => 'actions-edit-upload',
            'callbackAction' => 'uploadFile'
        ],
        'new' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new',
            'iconIdentifier' => 'actions-document-new',
            'callbackAction' => 'createFile'
        ],
        'info' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info',
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'openInfoPopUp'
        ],
        'divider' => [
            'type' => 'divider'
        ],
        'pasteInto' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteinto',
            'iconIdentifier' => 'actions-document-paste-into',
            'callbackAction' => 'pasteFileInto'
        ],
    ];

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'sys_file_storage';
    }

    /**
     * @return bool
     */
    protected function canShowInfo(): bool
    {
        return $this->backendUser->check('tables_select', 'sys_file_storage');
    }

    /**
     * @return bool
     */
    protected function canBeEdited(): bool
    {
        return $this->backendUser->check('tables_modify', 'sys_file_storage');
    }
}
