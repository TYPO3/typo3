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

namespace TYPO3\CMS\Backend\Tests\Functional\Sidebar\Fixtures;

use TYPO3\CMS\Backend\Attribute\AsSidebarComponent;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentContext;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentInterface;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentResult;

#[AsSidebarComponent(
    identifier: 'test-component-b-before',
    before: ['test-component-a'],
)]
final class TestComponentBBefore implements SidebarComponentInterface
{
    public function getResult(SidebarComponentContext $context): SidebarComponentResult
    {
        return new SidebarComponentResult('test-component-b-before', '<div>Component B (before A)</div>');
    }

    public function hasAccess(SidebarComponentContext $context): bool
    {
        return true;
    }
}
