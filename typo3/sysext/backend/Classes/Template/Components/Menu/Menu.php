<?php
namespace TYPO3\CMS\Backend\Template\Components\Menu;

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
 * Menu
 */
class Menu
{
    /**
     * Menu Identifier
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * Label of the Menu (useful for Selectbox menus)
     *
     * @var string
     */
    protected $label = '';

    /**
     * Container for menuitems
     *
     * @var array
     */
    protected $menuItems = [];

    /**
     * Get the label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label LabelText for the menu (accepts LLL syntax)
     *
     * @return Menu
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set identifier
     *
     * @param string $identifier Menu Identifier
     *
     * @return Menu
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Adds a new menuItem
     *
     * @param MenuItem $menuItem The menuItem to add to the menu
     *
     * @throws \InvalidArgumentException In case a menuItem is not valid
     *
     * @return void
     */
    public function addMenuItem(MenuItem $menuItem)
    {
        if (!$menuItem->isValid($menuItem)) {
            throw new \InvalidArgumentException('MenuItem "' . $menuItem->getTitle() . '" is not valid', 1442236317);
        }
        // @todo implement sorting of menu items
        // @todo maybe even things like spacers/sections?
        $this->menuItems[] = clone $menuItem;
    }

    /**
     * Get menu items
     *
     * @return array
     */
    public function getMenuItems()
    {
        return $this->menuItems;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * MenuItem Factory Method
     *
     * @return MenuItem
     */
    public function makeMenuItem()
    {
        $menuItem = GeneralUtility::makeInstance(MenuItem::class);
        return $menuItem;
    }

    /**
     * Validation function
     *
     * @param Menu $menu The menu to validate
     *
     * @return bool
     */
    public function isValid(Menu $menu)
    {
        return trim($menu->getIdentifier()) !== '';
    }
}
