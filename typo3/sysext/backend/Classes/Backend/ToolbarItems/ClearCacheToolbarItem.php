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

namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Render cache clearing toolbar item.
 * Adds a dropdown if there are more than one item to clear (usually for admins to render the flush all caches).
 * The dropdown items can be manipulated using ModifyClearCacheActionsEvent.
 */
class ClearCacheToolbarItem implements ToolbarItemInterface
{
    protected array $cacheActions = [];
    protected array $optionValues = [];

    public function __construct(
        UriBuilder $uriBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $isAdmin = $this->getBackendUser()->isAdmin();
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        // Clear all page-related caches
        if ($isAdmin || ($userTsConfig['options.']['clearCache.']['pages'] ?? false)) {
            $this->cacheActions[] = [
                'id' => 'pages',
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:flushPageCachesTitle',
                'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:flushPageCachesDescription',
                'href' => (string)$uriBuilder->buildUriFromRoute('tce_db', ['cacheCmd' => 'pages']),
                'iconIdentifier' => 'actions-system-cache-clear-impact-low',
            ];
            $this->optionValues[] = 'pages';
        }

        // Clearing of all caches is only shown if explicitly enabled via TSConfig
        // or if BE-User is admin and the TSconfig explicitly disables the possibility for admins.
        // This is useful for big production systems where admins accidentally could slow down the system.
        if (($userTsConfig['options.']['clearCache.']['all'] ?? false)
            || ($isAdmin && (bool)($userTsConfig['options.']['clearCache.']['all'] ?? true))
        ) {
            $this->cacheActions[] = [
                'id' => 'all',
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:flushAllCachesTitle2',
                'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:flushAllCachesDescription2',
                'href' => (string)$uriBuilder->buildUriFromRoute('tce_db', ['cacheCmd' => 'all']),
                'iconIdentifier' => 'actions-system-cache-clear-impact-high',
            ];
            $this->optionValues[] = 'all';
        }

        $event = new ModifyClearCacheActionsEvent($this->cacheActions, $this->optionValues);
        $event = $eventDispatcher->dispatch($event);
        $this->cacheActions = $event->getCacheActions();
        $this->optionValues = $event->getCacheActionIdentifiers();
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        foreach ($this->optionValues as $value) {
            if ($backendUser->getTSConfig()['options.']['clearCache.'][$value] ?? false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render clear cache icon, based on the option if there is more than one icon or just one.
     */
    public function getItem(): string
    {
        if ($this->hasDropDown()) {
            return $this->getFluidTemplateObject()->render('ToolbarItems/ClearCacheToolbarItem');
        }
        $view = $this->getFluidTemplateObject();
        $cacheAction = end($this->cacheActions);
        $view->assignMultiple([
                'link'  => $cacheAction['href'],
                'title' => $cacheAction['title'],
                'iconIdentifier'  => $cacheAction['iconIdentifier'],
            ]);
        return $view->render('ToolbarItems/ClearCacheToolbarItemSingle');
    }

    /**
     * Render drop-down.
     */
    public function getDropDown(): string
    {
        $view = $this->getFluidTemplateObject();
        $view->assign('cacheActions', $this->cacheActions);
        return $view->render('ToolbarItems/ClearCacheToolbarItemDropDown');
    }

    /**
     * No additional attributes needed.
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * This item has a drop-down, if there is more than one cache action available for the current Backend user.
     */
    public function hasDropDown(): bool
    {
        return count($this->cacheActions) > 1;
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 25;
    }

    protected function getFluidTemplateObject(): BackendTemplateView
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        return $view;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
