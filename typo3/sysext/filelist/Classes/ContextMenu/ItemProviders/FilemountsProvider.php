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
 * Provides click menu items for filemounts
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FilemountsProvider extends FileProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
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
        'divider' => [
            'type' => 'divider'
        ],
        'pasteInto' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.pasteinto',
            'iconIdentifier' => 'actions-document-paste-into',
            'callbackAction' => 'pasteFileInto'
        ]
    ];

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'sys_filemounts';
    }
}
