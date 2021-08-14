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
class NavigationComponentTreeCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkTreeExpandsAndCollapseByPageModule(ApplicationTester $I): void
    {
        $treeArea = '.scaffold-content-navigation-expanded';
        $I->wantTo('check Page Module for Expands And Collapse');
        $I->click('Page');
        $I->waitForElement($treeArea);
        $I->see('New TYPO3 site', $treeArea);

        $I->wantTo('check Page Module for Collapse');
        $I->click('button.scaffold-content-navigation-switcher-close');
        $I->waitForElementNotVisible($treeArea);
        $I->cantSee('New TYPO3 site', $treeArea);

        $I->wantTo('check Page Module for Expands');
        $I->click('button.scaffold-content-navigation-switcher-open');
        $I->waitForElement($treeArea);
        $I->see('New TYPO3 site', $treeArea);
    }

    public function checkTreeExpandsAndCollapseByFileModule(ApplicationTester $I): void
    {
        $treeArea = '.scaffold-content-navigation-expanded';

        $I->wantTo('check File Module for Expands And Collapse');
        $I->click('Filelist');
        $I->waitForElement($treeArea);
        $I->see('fileadmin', $treeArea);

        $I->wantTo('check File Module for Collapse');
        $I->click('button.scaffold-content-navigation-switcher-close');
        $I->waitForElementNotVisible($treeArea);
        $I->cantSee('fileadmin', $treeArea);

        $I->wantTo('check File Module for Expands');
        $I->click('button.scaffold-content-navigation-switcher-open');
        $I->waitForElement($treeArea);
        $I->see('fileadmin', $treeArea);
    }
}
