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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MenuTest extends UnitTestCase
{
    /**
     * Try setting an empty menu
     */
    #[Test]
    public function isMenuValidBlankCallExpectFalse(): void
    {
        $menu = new Menu();
        $isValid = $menu->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Set a valid menu
     */
    #[Test]
    public function isMenuValidValidMenuWithDefaultsExpectTrue(): void
    {
        $menu = new Menu();
        $menu->setIdentifier('husel');
        $isValid = $menu->isValid();
        self::assertTrue($isValid);
    }

    /**
     * Set a valid menu
     */
    #[Test]
    #[IgnoreDeprecations]
    public function makeMenuAllGoodExpectTrue(): void
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
     */
    #[Test]
    #[IgnoreDeprecations]
    public function getMenusRemovedEmptyMenusExpectsEquals(): void
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
            'Foo' => $menu2,
        ];

        self::assertEquals($expected, $result);
    }
}
