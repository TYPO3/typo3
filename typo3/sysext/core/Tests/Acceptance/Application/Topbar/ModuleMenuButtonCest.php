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

namespace TYPO3\core\Tests\Acceptance\Application\Topbar;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Acceptance test for the menu button in the topbar
 */
class ModuleMenuButtonCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkModelMenuButtonFromBigToSmallScreen(ApplicationTester $I): void
    {
        $I->wantTo('see the module menu button behavior when shrinking the window');

        $I->seeElement('.scaffold-modulemenu-expanded');
        $I->seeElement('.modulemenu-indicator');
        $I->seeElement('.modulemenu-icon');

        $I->amGoingTo('collapse the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');

        $I->expectTo('see only the module menu icon');
        $I->cantSeeElement('.scaffold-modulemenu-expanded');
        $I->cantSeeElement('.modulemenu-indicator');
        $I->seeElement('.modulemenu-icon');

        $I->amGoingTo('shrink the window');
        $I->resizeWindow(680, 420);
        $I->expectTo('see no module menu');
        $I->cantSeeElement('.modulemenu-icon');

        $I->amGoingTo('expand the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');
        $I->expectTo('see the expanded module menu');
        $I->seeElement('.modulemenu-indicator');
        $I->seeElement('.modulemenu-icon');
    }

    public function checkModelMenuButtonFromSmallToBigScreen(ApplicationTester $I): void
    {
        $I->wantTo('see the module menu button behavior when enlarging the window');

        $I->amGoingTo('shrink the window');
        $I->resizeWindow(320, 400);
        $I->expectTo('see the module menu');
        $I->seeElement('.scaffold-modulemenu-expanded');
        $I->seeElement('.modulemenu-indicator');
        $I->seeElement('.modulemenu-icon');

        $I->amGoingTo('collapse the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');

        $I->expectTo('see no module menu');
        $I->cantSeeElement('.scaffold-modulemenu-expanded');
        $I->cantSeeElement('.modulemenu-indicator');
        $I->cantSeeElement('.modulemenu-icon');

        $I->amGoingTo('enlarge the window');
        $I->resizeWindow(1280, 960);
        $I->expectTo('see the module menu icon');
        $I->seeElement('.modulemenu-icon');

        $I->amGoingTo('expand the module menu');
        $I->click('button.t3js-topbar-button-modulemenu span[data-identifier="actions-menu"]');

        $I->expectTo('see the full module menu');
        $I->seeElement('.scaffold-modulemenu-expanded');
        $I->seeElement('.modulemenu-indicator');
        $I->seeElement('.modulemenu-icon');
    }
}
