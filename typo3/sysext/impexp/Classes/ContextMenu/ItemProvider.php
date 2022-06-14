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

namespace TYPO3\CMS\Impexp\ContextMenu;

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context menu item provider adding export and import items
 *
 * @internal This class is not considered part of the public TYPO3 API.
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
            'callbackAction' => 'exportT3d',
        ],
        'importT3d' => [
            'type' => 'item',
            'label' => 'LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:import',
            'iconIdentifier' => 'actions-document-import-t3d',
            'callbackAction' => 'importT3d',
        ],
    ];

    /**
     * Export item is added for all database records except files
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table !== 'sys_file';
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
                $canRender = $this->backendUser->isExportEnabled();
                break;
            case 'importT3d':
                $canRender = $this->table === 'pages' && $this->backendUser->isImportEnabled();
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
        $attributes = [
            'data-callback-module' => 'TYPO3/CMS/Impexp/ContextMenuActions',
        ];

        // Add action url for items
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        switch ($itemName) {
            case 'exportT3d':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tx_impexp_export'));
                break;
            case 'importT3d':
                $attributes['data-action-url'] = htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tx_impexp_import'));
                break;
        }

        return $attributes;
    }
}
