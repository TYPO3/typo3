<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Beuser\ContextMenu;

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

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\PageProvider;

/**
 * @internal This class is a TYPO3 core-internal hook implementation and is not considered part of the Public TYPO3 API.
 */
class ItemProvider extends PageProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'permissions' => [
            'type' => 'item',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:CM_perms',
            'iconIdentifier' => 'actions-lock',
            'callbackAction' => 'openPermissionsModule'
        ],
    ];

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
        return $this->canShowPermissionsModule();
    }

    /**
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
     * This priority should be lower than priority of the PageProvider, so it's evaluated after the PageProvider
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 60;
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        return ['data-callback-module' => 'TYPO3/CMS/Beuser/ContextMenuActions'];
    }

    /**
     * Checks if the page is allowed to show permission module
     *
     * @return bool
     */
    protected function canShowPermissionsModule(): bool
    {
        return $this->canBeEdited() && $this->backendUser->check('modules', 'system_BeuserTxPermission');
    }
}
