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

namespace TYPO3\CMS\Backend\Template\Components;

use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Central registry for managing menus in the backend module document header.
 * The MenuRegistry is part of the DocHeaderComponent and holds all menus that should
 * be displayed in the module's header area.
 *
 * Menus are typically used to provide navigation between different views or modes within
 * a module (e.g., switching between list/grid view, or different content types).
 *
 * Example:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     // Get the menu registry from the module template
 *     $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
 *
 *     // Create and configure a menu
 *     $menu = $this->componentFactory->createMenu();
 *     $menu->setIdentifier('viewSelector')->setLabel('View');
 *
 *     // Add menu items
 *     $listItem = $this->componentFactory->createMenuItem()
 *         ->setTitle('List')
 *         ->setHref('/module?view=list')
 *         ->setActive($currentView === 'list');
 *     $menu->addMenuItem($listItem);
 *
 *     // Register the menu
 *     $menuRegistry->addMenu($menu);
 * }
 * ```
 */
class MenuRegistry
{
    /**
     * Internal array that stores all registered menus
     *
     * @var array<string, Menu>
     */
    protected array $menus = [];

    /**
     * Adds a menu to the registry
     *
     * @throws \InvalidArgumentException In case a menu is not valid
     */
    public function addMenu(Menu $menu): static
    {
        if (!$menu->isValid()) {
            throw new \InvalidArgumentException('Menu "' . $menu->getIdentifier() . '" is not valid', 1442236362);
        }
        $this->menus[$menu->getIdentifier()] = clone $menu;
        return $this;
    }

    /**
     * Returns all menus in an abstract array
     *
     * @return Menu[]
     */
    public function getMenus(): array
    {
        foreach ($this->menus as $key => $menu) {
            if (empty($menu->getMenuItems())) {
                unset($this->menus[$key]);
            }
        }
        return $this->menus;
    }

    /**
     * @deprecated since v14, will be removed in v15. Use GeneralUtility::makeInstance(Menu::class) directly or inject ComponentFactory and use createMenu().
     */
    public function makeMenu(): Menu
    {
        // @todo Activate once core is migrated
        // trigger_error('MenuRegistry::makeMenu() is deprecated and will be removed in TYPO3 v15. Use GeneralUtility::makeInstance(Menu::class) directly or inject ComponentFactory and use createMenu().', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(Menu::class);
    }
}
