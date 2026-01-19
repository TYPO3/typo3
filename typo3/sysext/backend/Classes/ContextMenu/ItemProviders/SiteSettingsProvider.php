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

namespace TYPO3\CMS\Backend\ContextMenu\ItemProviders;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Context menu item provider adding "Site Settings" and "Edit Site Configuration" for pages that are site roots
 */
final class SiteSettingsProvider extends AbstractProvider
{
    private const ITEMS_CONFIGURATION = [
        'editSiteConfiguration' => [
            'label' => 'backend.siteconfiguration:contextMenu.editSiteConfiguration',
            'iconIdentifier' => 'actions-window',
            'callbackAction' => 'openSiteConfiguration',
        ],
        'editSiteSettings' => [
            'label' => 'backend.siteconfiguration:contextMenu.editSiteSettings',
            'iconIdentifier' => 'actions-window-cog',
            'callbackAction' => 'openSiteSettings',
        ],
    ];

    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly UriBuilder $uriBuilder,
    ) {
        parent::__construct();
    }

    public function canHandle(): bool
    {
        // Site configuration module requires admin access
        return $this->table === 'pages' && $this->backendUser->isAdmin();
    }

    public function getPriority(): int
    {
        return 60;
    }

    public function addItems(array $items): array
    {
        $this->initDisabledItems();

        // Add site items after "edit" item
        $localItems = $this->prepareItems(self::ITEMS_CONFIGURATION);
        $position = array_search('edit', array_keys($items), true);
        if ($position !== false) {
            $items = [
                ...array_slice($items, 0, $position + 1, true),
                ...$localItems,
                ...array_slice($items, $position + 1, null, true),
            ];
        } else {
            $items = [...$items, ...$localItems];
        }

        return $items;
    }

    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }

        if ($itemName === 'editSiteSettings' || $itemName === 'editSiteConfiguration') {
            return $this->canOpenSiteSettings();
        }

        return true;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        $pageId = (int)$this->identifier;
        try {
            $site = $this->siteFinder->getSiteByRootPageId($pageId);

            if ($itemName === 'editSiteSettings') {
                return [
                    'data-site-settings-url' => (string)$this->uriBuilder->buildUriFromRoute(
                        'site_configuration.editSettings',
                        ['site' => $site->getIdentifier()]
                    ),
                ];
            }

            if ($itemName === 'editSiteConfiguration') {
                return [
                    'data-site-configuration-url' => (string)$this->uriBuilder->buildUriFromRoute(
                        'site_configuration.edit',
                        ['site' => $site->getIdentifier()]
                    ),
                ];
            }
        } catch (SiteNotFoundException) {
            return [];
        }

        return [];
    }

    private function canOpenSiteSettings(): bool
    {
        // Check if this page is a site root
        $pageId = (int)$this->identifier;
        if ($pageId <= 0) {
            return false;
        }

        try {
            $this->siteFinder->getSiteByRootPageId($pageId);
            return true;
        } catch (SiteNotFoundException) {
            return false;
        }
    }
}
