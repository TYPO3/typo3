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

namespace TYPO3\CMS\Backend\Template\Components\Menu;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents a navigation menu in the backend module document header, typically rendered
 * as a dropdown selector that allows users to switch between different views or modes
 * within a module.
 *
 * Menus consist of multiple MenuItems and are registered with the MenuRegistry in the
 * DocHeaderComponent. The menu is automatically rendered in the module's document header.
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
 *     $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
 *     $menu = $this->componentFactory->createMenu();
 *     $menu->setIdentifier('myModuleMenu')
 *         ->setLabel('Select View');
 *
 *     $menuItem1 = $this->componentFactory->createMenuItem()
 *         ->setTitle('List View')
 *         ->setHref($listViewUrl)
 *         ->setActive(true);
 *     $menu->addMenuItem($menuItem1);
 *
 *     $menuItem2 = $this->componentFactory->createMenuItem()
 *         ->setTitle('Grid View')
 *         ->setHref($gridViewUrl);
 *     $menu->addMenuItem($menuItem2);
 *
 *     $menuRegistry->addMenu($menu);
 * }
 * ```
 */
class Menu
{
    protected string $identifier = '';

    /**
     * Label of the Menu (displayed as the dropdown label)
     */
    protected string $label = '';

    protected array $menuItems = [];

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getDataIdentifier(): string
    {
        $dataMenuIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($this->identifier);
        return str_replace('_', '-', $dataMenuIdentifier);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getMenuItems(): array
    {
        return $this->menuItems;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @param string $label LabelText for the menu (accepts LLL syntax)
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Adds a new menuItem
     *
     * @param MenuItem $menuItem The menuItem to add to the menu
     *
     * @throws \InvalidArgumentException In case a menuItem is not valid
     */
    public function addMenuItem(MenuItem $menuItem): static
    {
        if (!$menuItem->isValid()) {
            throw new \InvalidArgumentException('MenuItem "' . $menuItem->getTitle() . '" is not valid', 1442236317);
        }
        // @todo implement sorting of menu items
        // @todo maybe even things like spacers/sections?
        $this->menuItems[] = clone $menuItem;
        return $this;
    }

    /**
     * @deprecated since v14, will be removed in v15. Use GeneralUtility::makeInstance(MenuItem::class) directly or inject ComponentFactory and use createMenuItem().
     */
    public function makeMenuItem(): MenuItem
    {
        // @todo Activate once core is migrated
        // trigger_error('Menu::makeMenuItem() is deprecated and will be removed in TYPO3 v15. Use GeneralUtility::makeInstance(MenuItem::class) directly or inject ComponentFactory and use createMenuItem().', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(MenuItem::class);
    }

    public function isValid(): bool
    {
        return trim($this->getIdentifier()) !== '';
    }
}
