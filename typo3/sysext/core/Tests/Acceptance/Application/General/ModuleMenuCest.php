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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\General;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Module Menu tests
 */
final class ModuleMenuCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkIfModuleMenuIsCollapsible(ApplicationTester $I): void
    {
        // A sub-element of web module is shown
        $I->waitForElementVisible('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');
        $I->seeElement('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');

        // Collapse web module and verify sub elements are hidden
        $I->wantTo('collapse the menu element');
        $I->waitForElementVisible('[data-modulemenu-identifier="web"]');
        $I->click('[data-modulemenu-identifier="web"]');
        $I->waitForElementNotVisible('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');
        $I->dontSeeElement('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');

        // Expand again and verify sub elements are shown
        $I->wantTo('expand the menu element again');
        $I->click('[data-modulemenu-identifier="web"]');
        $I->waitForElementVisible('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');
        $I->seeElement('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');
    }

    public function selectingAModuleDoesHighlightIt(ApplicationTester $I): void
    {
        $I->seeNumberOfElements('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action', [2, 20]);

        $I->wantTo('check that the second element has no "modulemenu-action-active" class\'');
        $I->cantSeeElement('[data-modulemenu-identifier="web"].modulemenu-action-active');
        $I->click('[data-modulemenu-identifier="web_list"]');

        $I->wantTo('see that the second element has an "modulemenu-action-active" class');
        $I->canSeeElement('[data-modulemenu-identifier="web_list"].modulemenu-action-active');
    }
}
