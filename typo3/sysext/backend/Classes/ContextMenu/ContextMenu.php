<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\ContextMenu;

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
 * Class for generating the click menu
 * @internal
 */
class ContextMenu
{
    /**
     * Click menu item providers shipped with EXT:backend
     *
     * @var array
     */
    protected $itemProviders = [
        ItemProviders\PageProvider::class,
        ItemProviders\RecordProvider::class
    ];

    /**
     * @param string $table
     * @param string $identifier
     * @param string $context
     * @return array
     */
    public function getItems(string $table, string $identifier, string $context=''): array
    {
        $items = [];
        $itemsProviders = $this->getAvailableProviders($table, $identifier, $context);

        /** @var $provider \TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface */
        foreach ($itemsProviders as $provider) {
            $items = $provider->addItems($items);
        }
        return $this->cleanItems($items);
    }

    /**
     * @param string $table
     * @param string $identifier
     * @param string $context
     * @return array of \TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface
     */
    protected function getAvailableProviders(string $table, string $identifier, string $context): array
    {
        $providers = $this->itemProviders;
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'])) {
            $providers = array_merge($this->itemProviders, $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders']);
        }

        $availableProviders = [];
        foreach ($providers as $providerClass) {
            $provider = GeneralUtility::makeInstance($providerClass, $table, $identifier, $context);
            if ($provider->canHandle()) {
                $priority = $provider->getPriority();
                $availableProviders[$priority] = $provider;
            }
        }
        krsort($availableProviders);
        return $availableProviders;
    }

    /**
     * Clean up double dividers.
     * Don't render menu when there are no item or submenu.
     *
     * @param array $items
     * @return array
     */
    protected function cleanItems(array $items): array
    {
        $canRender = false;
        $prevItemWasDivider = false;
        foreach ($items as $key => $item) {
            if ($item['type'] === 'item') {
                $canRender = true;
                $prevItemWasDivider = false;
                continue;
            }
            if ($item['type'] === 'divider') {
                if ($prevItemWasDivider === true) {
                    unset($items[$key]);
                } else {
                    $prevItemWasDivider = true;
                }
                continue;
            }
            if ($item['type'] === 'submenu') {
                $childItems = $this->cleanItems($item['childItems']);
                if (empty($childItems)) {
                    unset($items[$key]);
                } else {
                    $items[$key]['childItems'] = $childItems;
                    $canRender = true;
                    $prevItemWasDivider = false;
                }
                continue;
            }
        }
        //Remove first and last divider
        $fistItem = reset($items);
        if ($fistItem['type'] === 'divider') {
            $key = key($items);
            unset($items[$key]);
        }
        $lastItem = end($items);
        if ($lastItem['type'] === 'divider') {
            $key = key($items);
            unset($items[$key]);
        }
        //no menu when there are no item or submenu
        if (!$canRender) {
            $items = [];
        }
        return $items;
    }
}
