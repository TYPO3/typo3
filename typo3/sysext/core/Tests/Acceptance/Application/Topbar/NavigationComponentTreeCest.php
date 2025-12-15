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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Topbar;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Acceptance test for the Navigation Component Tree
 */
final class NavigationComponentTreeCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkTreeExpandsAndCollapseByPageModule(ApplicationTester $I): void
    {
        $navigationContainer = 'typo3-backend-content-navigation[identifier="backend"]';
        $navigationExpanded = $navigationContainer . ':not([navigation-collapsed])';
        $navigationCollapsed = $navigationContainer . '[navigation-collapsed]';
        $navigationSlot = $navigationContainer . ' [slot="navigation"]';

        $I->click('Layout');
        $I->waitForElement($navigationExpanded);
        $I->see('New TYPO3 site', $navigationSlot);

        $I->click($navigationContainer . ' typo3-backend-content-navigation-toggle[action="collapse"]');
        $I->waitForElement($navigationCollapsed);

        $I->switchToContentFrame();
        $I->click('typo3-backend-content-navigation-toggle[action="expand"]');
        $I->switchToMainFrame();
        $I->waitForElement($navigationExpanded);
        $I->see('New TYPO3 site', $navigationSlot);
    }

    public function checkTreeExpandsAndCollapseByFileModule(ApplicationTester $I): void
    {
        $navigationContainer = 'typo3-backend-content-navigation[identifier="backend"]';
        $navigationExpanded = $navigationContainer . ':not([navigation-collapsed])';
        $navigationCollapsed = $navigationContainer . '[navigation-collapsed]';
        $navigationSlot = $navigationContainer . ' [slot="navigation"]';

        $I->click('Media');

        // Make sure 'fileadmin' is selected since FileClipboardCest clicks around on file tree, too.
        // @todo: Working on file tree could be extracted like Helper/PageTree
        $I->waitForText('fileadmin');
        $I->click('//*[@id="typo3-filestoragetree-tree"]//*[text()="fileadmin"]/..');

        $I->waitForElement($navigationExpanded);
        $I->see('fileadmin', $navigationSlot);

        $I->click($navigationContainer . ' typo3-backend-content-navigation-toggle[action="collapse"]');
        $I->waitForElement($navigationCollapsed);

        $I->switchToContentFrame();
        $I->click('typo3-backend-content-navigation-toggle[action="expand"]');
        $I->switchToMainFrame();
        $I->waitForElement($navigationExpanded);
        $I->see('fileadmin', $navigationSlot);
    }
}
