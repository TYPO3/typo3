<?php
namespace TYPO3\CMS\Backend\Tests\Template\Components;

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
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case for Menu
 */
class MenuTest extends UnitTestCase
{
    /**
     * Try setting an empty menu
     *
     * @test
     * @return void
     */
    public function isMenuValidBlankCallExpectFalse()
    {
        $menu = new Menu();
        $isValid = $menu->isValid($menu);
        $this->assertFalse($isValid);
    }

    /**
     * Set a valid menu
     *
     * @test
     * @return void
     */
    public function isMenuValidValidMenuWithDefaultsExpectTrue()
    {
        $menu = new Menu();
        $menu->setIdentifier('husel');
        $isValid = $menu->isValid($menu);
        $this->assertTrue($isValid);
    }

    /**
     * Set a valid menu
     *
     * @test
     * @return void
     */
    public function makeMenuAllGoodExpectTrue()
    {
        $menuRegistry = new MenuRegistry();
        $result = $menuRegistry->makeMenu()->setLabel('MenuLabel')->setIdentifier('MenuIdent');
        $expected = new Menu();
        $expected->setIdentifier('MenuIdent');
        $expected->setLabel('MenuLabel');
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests if empty menus get removed from the stack
     *
     * @test
     * @return void
     */
    public function getMenusremovedEmptyMenusExpectsEquals()
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

        $this->assertEquals($expected, $result);
    }
}
