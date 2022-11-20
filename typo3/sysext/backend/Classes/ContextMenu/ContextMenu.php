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

namespace TYPO3\CMS\Backend\ContextMenu;

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\ItemProvidersRegistry;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface;

/**
 * Class for generating the click menu
 * @internal
 */
class ContextMenu
{
    protected ItemProvidersRegistry $itemProvidersRegistry;

    public function __construct(ItemProvidersRegistry $itemProvidersRegistry)
    {
        $this->itemProvidersRegistry = $itemProvidersRegistry;
    }

    public function getItems(string $table, string $identifier, string $context = ''): array
    {
        $items = [];
        foreach ($this->getAvailableProviders($table, $identifier, $context) as $provider) {
            $items = $provider->addItems($items);
        }
        return $this->cleanItems($items);
    }

    /**
     * @return ProviderInterface[]
     */
    protected function getAvailableProviders(string $table, string $identifier, string $context): array
    {
        $providers = $this->itemProvidersRegistry->getItemProviders();
        $availableProviders = [];
        foreach ($providers as $provider) {
            $provider->setContext($table, $identifier, $context);
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

        if ($canRender) {
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
        } else {
            //no menu when there are no item or submenu
            $items = [];
        }
        return $items;
    }
}
