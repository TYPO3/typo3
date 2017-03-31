<?php
namespace TYPO3\core\Tests\Acceptance\Backend\Topbar;

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
 * Acceptance test for the menu button in the topbar
 */
class ModuleMenuButtonCest
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
    public function checkModelMenuButtomFromBigToSmallScreen(Admin $I)
    {
        $I->wantTo('see the module menu button behavior when shrinking the window');

        $I->seeElement('.scaffold-modulemenu-expanded');
        $I->see('Web', 'span.modulemenu-group-title');
        $I->seeElement('.modulemenu-group-icon');

        $I->amGoingTo('collapse the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');

        $I->expectTo('see only the module menu icon');
        $I->cantSeeElement('.scaffold-modulemenu-expanded');
        $I->cantSee('Web', 'span.modulemenu-group-title');
        $I->seeElement('.modulemenu-group-icon');

        $I->amGoingTo('shrink the window');
        $I->resizeWindow(680, 420);
        $I->expectTo('see no module menu');
        $I->cantSeeElement('.modulemenu-group-icon');

        $I->amGoingTo('expand the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');
        $I->expectTo('see the expanded module menu');
        $I->see('Web', 'span.modulemenu-group-title');
        $I->seeElement('.modulemenu-group-icon');
    }

    /**
     * @param Admin $I
     */
    public function checkModelMenuButtomFromSmallToBigScreen(Admin $I)
    {
        $I->wantTo('see the module menu button behavior when enlarging the window');

        $I->amGoingTo('shrink the window');
        $I->resizeWindow(320, 400);
        $I->expectTo('see the module menu');
        $I->seeElement('.scaffold-modulemenu-expanded');
        $I->see('Web', 'span.modulemenu-group-title');
        $I->seeElement('.modulemenu-group-icon');

        $I->amGoingTo('collapse the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');

        $I->expectTo('see no module menu');
        $I->cantSeeElement('.scaffold-modulemenu-expanded');
        $I->cantSee('Web', 'span.modulemenu-group-title');
        $I->cantSeeElement('.modulemenu-group-icon');

        $I->amGoingTo('enlarge the window');
        $I->resizeWindow(1280, 960);
        $I->expectTo('see the module menu icon');
        $I->seeElement('.modulemenu-group-icon');

        $I->amGoingTo('expand the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');

        $I->expectTo('see the full module menu');
        $I->seeElement('.scaffold-modulemenu-expanded');
        $I->see('Web', 'span.modulemenu-group-title');
    }
}
