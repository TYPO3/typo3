<?php
declare(strict_types=1);
namespace TYPO3\CMS\Version\ContextMenu;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Context menu item provider for version module
 */
class ItemProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'versioning' => [
            'type' => 'item',
            'label' => 'LLL:EXT:version/Resources/Private/Language/locallang.xlf:title',
            'iconIdentifier' => 'actions-version-page-open',
            'callbackAction' => 'openVersionModule'
        ]
    ];

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return isset($GLOBALS['TCA'][$this->table]) && $GLOBALS['TCA'][$this->table]['ctrl']['versioningWS'];
    }

    /**
     * This needs to be lower than priority of the RecordProvider
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 55;
    }

    /**
     * Registers custom JS module with item onclick behaviour
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $url = BackendUtility::getModuleUrl('web_txversionM1', ['table' => $this->table, 'uid' => $this->identifier]);
        return [
            'data-action-url' => htmlspecialchars($url),
            'data-callback-module' => 'TYPO3/CMS/Version/ContextMenuActions'];
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

        if (isset($items['delete'])) {
            $localItems = $this->prepareItems($this->itemsConfiguration);
            $position = array_search('delete', array_keys($items), true);

            $beginning = array_slice($items, 0, $position+1, true);
            $end = array_slice($items, $position, null, true);

            $items = $beginning + $localItems + $end;
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
            case 'versioning':
                $canRender = (int)$this->identifier > 0  && $this->backendUser->check('modules', 'web_txversionM1');
                break;
        }
        return $canRender;
    }
}
