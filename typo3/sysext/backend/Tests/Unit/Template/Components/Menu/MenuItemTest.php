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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components\Menu;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MenuItemTest extends UnitTestCase
{
    /**
     * Try a blank menu Item
     */
    #[Test]
    public function isMenuItemValidBlankCallExpectFalse(): void
    {
        $menuItem = new MenuItem();
        $isValid = $menuItem->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Try omitting the title and a Href
     */
    #[Test]
    public function isMenuItemValidOmittedHrefAndRouteExpectFalse(): void
    {
        $menuItem = new MenuItem();
        $menuItem->setTitle('huhu');
        $isValid = $menuItem->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Try omitting the title
     */
    #[Test]
    public function isMenuItemValidOmittedTitleExpectFalse(): void
    {
        $menuItem = new MenuItem();
        $menuItem->setHref('husel');
        $isValid = $menuItem->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Set a valid title and href
     */
    #[Test]
    public function isMenuItemValidSetValidHrefAndTitleExpectTrue(): void
    {
        $menuItem = new MenuItem();
        $menuItem->setTitle('husel')->setHref('husel');
        $isValid = $menuItem->isValid();
        self::assertTrue($isValid);
    }
}
