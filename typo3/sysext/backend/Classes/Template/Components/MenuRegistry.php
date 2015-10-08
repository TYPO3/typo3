<?php
namespace TYPO3\CMS\Backend\Template\Components;

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

use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * MenuRegistry
 */
class MenuRegistry
{
    /**
     * Internal array that stores all registered menus
     *
     * @var array
     */
    protected $menus = [];

    /**
     * Adds a menu to the registry
     *
     * @param Menu $menu Menu object to add to the menuRegistry
     *
     * @throws \InvalidArgumentException In case a menu is not valid
     *
     * @return void
     */
    public function addMenu(Menu $menu)
    {
        if (!$menu->isValid($menu)) {
            throw new \InvalidArgumentException('Menu "' . $menu->getIdentifier() . '" is not valid', 1442236362);
        }
        $this->menus[$menu->getIdentifier()] = clone $menu;
    }

    /**
     * Returns all menus in an abstract array
     *
     * @return array
     */
    public function getMenus()
    {
        // @todo do we want to provide a hook here?
        /**
         * For Code Completion
         *
         * @var int $key
         * @var Menu $menu
         */
        foreach ($this->menus as $key => $menu) {
            if (empty($menu->getMenuItems())) {
                unset($this->menus[$key]);
            }
        }
        return $this->menus;
    }

    /**
     * MenuFactory method
     *
     * @return Menu
     */
    public function makeMenu()
    {
        $menu = GeneralUtility::makeInstance(Menu::class);
        return $menu;
    }
}
