<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Impexp\ContextMenu;

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

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;

/**
 * Context menu item provider adding export and import items
 * @internal this is a internal TYPO3 hook implementation and solely used for EXT:impexp and not part of TYPO3's Core API.
 */
class ItemProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'exportT3d' => [
            'type' => 'item',
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:export',
            'iconIdentifier' => 'actions-document-export-t3d',
            'callbackAction' => 'exportT3d'
        ],
        'importT3d' => [
            'type' => 'item',
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:import',
            'iconIdentifier' => 'actions-document-import-t3d',
            'callbackAction' => 'importT3d',
        ]
    ];

    /**
     * Export item is added for all database records except files
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return !in_array($this->table, ['sys_file', 'sys_filemounts', 'sys_file_storage'], true)
            && strpos($this->table, '-drag') === false;
    }

    /**
     * This needs to be lower than priority of the RecordProvider
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Adds import/export items to the "submenu" if available
     *
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        $this->initDisabledItems();
        $localItems = $this->prepareItems($this->itemsConfiguration);
        if (isset($items['more']['childItems'])) {
            $items['more']['childItems'] = $items['more']['childItems'] + $localItems;
        } else {
            $items += $localItems;
        }
        return $items;
    }

    /**
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'exportT3d':
                $canRender = true;
                break;
            case 'importT3d':
                $canRender = $this->table === 'pages' && $this->isImportEnabled();
                break;
        }
        return $canRender;
    }

    /**
     * Registers custom JS module with item onclick behaviour
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        return ['data-callback-module' => 'TYPO3/CMS/Impexp/ContextMenuActions'];
    }

    /**
     * Check if import functionality is available for current user
     */
    protected function isImportEnabled(): bool
    {
        return $this->backendUser->isAdmin()
            || !$this->backendUser->isAdmin() && (bool)($this->backendUser->getTSConfig()['options.']['impexp.']['enableImportForNonAdminUser'] ?? false);
    }
}
