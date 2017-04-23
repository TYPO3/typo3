<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Topbar;

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Acceptance test for the Navigation Component Tree
 */
class NavigationComponentTreeCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
    }

    /**
     * @param Admin $I
     */
    public function checkTreeExpandsAndCollapseByPageModule(Admin $I)
    {
        $treeArea = '.scaffold-content-navigation-expanded';
        $I->wantTo('check Page Module for Expands And Collapse');
        $I->click('Page');
        $I->waitForElement($treeArea);
        $I->see('New TYPO3 site', $treeArea);
        $I->wantTo('check Page Module for Collapse');
        $I->click('button.t3js-topbar-button-navigationcomponent');
        $I->waitForElementNotVisible($treeArea);
        $I->cantSee('New TYPO3 site', $treeArea);
        $I->wantTo('check Page Module for Expands');
        $I->click('button.t3js-topbar-button-navigationcomponent');
        $I->waitForElement($treeArea);

        $I->see('New TYPO3 site', $treeArea);
    }

    /**
     * @param Admin $I
     */
    public function checkTreeExpandsAndCollapseByFileModule(Admin $I)
    {
        $I->wantTo('check File Module for Expands And Collapse');
        $I->click('Filelist');
        $I->switchToIFrame('nav_frame');
        $I->waitForElement('.t3js-module-body');
        $I->see('fileadmin', '.t3js-module-body');
        $I->switchToIFrame();
        $I->wantTo('check File Module for Collapse');
        $I->click('button.t3js-topbar-button-navigationcomponent');
        $I->waitForElementNotVisible('.scaffold-content-navigation-expanded');
        $I->wantTo('check File Module for Expands');
        $I->click('button.t3js-topbar-button-navigationcomponent');
        $I->switchToIFrame('nav_frame');
        $I->waitForElement('.t3js-module-body');
        $I->see('fileadmin', '.t3js-module-body');
        $I->switchToIFrame();
    }
}
