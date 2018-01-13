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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Drag drop menu provider for legacy tree (used in filelist folder tree)
 */
class FileDragProvider extends \TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'copyInto' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.copyFolder_into',
            'iconIdentifier' => 'apps-pagetree-drag-move-into',
            'callbackAction' => 'dropCopyInto'
        ],
        'moveInto' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.moveFolder_into',
            'iconIdentifier' => 'apps-pagetree-drag-move-into',
            'callbackAction' => 'dropMoveInto'
        ]
    ];

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'folders-drag';
    }

    /**
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        return true;
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [
            'data-callback-module' => 'TYPO3/CMS/Filelist/ContextMenuActions',
            'data-drop-target' => htmlspecialchars(GeneralUtility::_GP('dstId'))
        ];

        return $attributes;
    }
}
