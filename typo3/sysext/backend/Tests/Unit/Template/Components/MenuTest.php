<?php

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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components;

use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for Menu
 */
class MenuTest extends UnitTestCase
{
    /**
     * Try setting an empty menu
     *
     * @test
     */
    public function isMenuValidBlankCallExpectFalse()
    {
        $menu = new Menu();
        $isValid = $menu->isValid($menu);
        self::assertFalse($isValid);
    }

    /**
     * Set a valid menu
     *
     * @test
     */
    public function isMenuValidValidMenuWithDefaultsExpectTrue()
    {
        $menu = new Menu();
        $menu->setIdentifier('husel');
        $isValid = $menu->isValid($menu);
        self::assertTrue($isValid);
    }

    /**
     * Set a valid menu
     *
     * @test
     */
    public function makeMenuAllGoodExpectTrue()
    {
        $menuRegistry = new MenuRegistry();
        $result = $menuRegistry->makeMenu()->setLabel('MenuLabel')->setIdentifier('MenuIdent');
        $expected = new Menu();
        $expected->setIdentifier('MenuIdent');
        $expected->setLabel('MenuLabel');
        self::assertEquals($expected, $result);
    }

    /**
     * Tests if empty menus get removed from the stack
     *
     * @test
     */
    public function getMenusRemovedEmptyMenusExpectsEquals()
    {
        $menuRegistry = new MenuRegistry();

        $menu1 = $menuRegistry->makeMenu();
        $menu1->setIdentifier('husel');
        $menu1->setLabel('Label of an empty Menu');
        $menuRegistry->addMenu($menu1);

        $menu2 = $menuRegistry->makeMenu()->setIdentifier('Foo');
        $item = $menu2->makeMenuItem()->setHref('#')->setTitle('Husel');
        $menu2->addMenuItem($item);

        $menuRegistry->addMenu($menu2);

        $result = $menuRegistry->getMenus();
        $expected = [
            'Foo' => $menu2
        ];

        self::assertEquals($expected, $result);
    }
}
