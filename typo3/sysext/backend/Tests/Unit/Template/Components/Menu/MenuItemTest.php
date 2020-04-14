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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components\Menu;

use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for MenuItem
 */
class MenuItemTest extends UnitTestCase
{
    /**
     * Try a blank menu Item
     *
     * @test
     */
    public function isMenuItemValidBlankCallExpectFalse()
    {
        $menuItem = new MenuItem();
        $isValid = $menuItem->isValid($menuItem);
        self::assertFalse($isValid);
    }

    /**
     * Try omitting the title and a Href
     *
     * @test
     */
    public function isMenuItemValidOmittedHrefAndRouteExpectFalse()
    {
        $menuItem = new MenuItem();
        $menuItem->setTitle('huhu');
        $isValid = $menuItem->isValid($menuItem);
        self::assertFalse($isValid);
    }

    /**
     * Try omitting the title
     *
     * @test
     */
    public function isMenuItemValidOmittedTitleExpectFalse()
    {
        $menuItem = new MenuItem();
        $menuItem->setHref('husel');
        $isValid = $menuItem->isValid($menuItem);
        self::assertFalse($isValid);
    }

    /**
     * Set a valid title and href
     *
     * @test
     */
    public function isMenuItemValidSetValidHrefAndTitleExpectTrue()
    {
        $menuItem = new MenuItem();
        $menuItem->setTitle('husel')->setHref('husel');
        $isValid = $menuItem->isValid($menuItem);
        self::assertTrue($isValid);
    }
}
