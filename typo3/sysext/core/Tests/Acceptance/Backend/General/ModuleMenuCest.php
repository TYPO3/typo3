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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\General;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Module Menu tests
 */
class ModuleMenuCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     */
    public function checkIfModuleMenuIsCollapsible(BackendTester $I)
    {
        // A sub-element of web module is shown
        $I->waitForElementVisible('#web + .modulemenu-group-container .modulemenu-action');
        $I->seeElement('#web + .modulemenu-group-container .modulemenu-action');

        // Collapse web module and verify sub elements are hidden
        $I->wantTo('collapse the menu element');
        $I->waitForElementVisible('#web');
        $I->click('#web');
        $I->waitForElementNotVisible('#web + .modulemenu-group-container .modulemenu-action');
        $I->dontSeeElement('#web + .modulemenu-group-container .modulemenu-action');

        // Expand again and verify sub elements are shown
        $I->wantTo('expand the menu element again');
        $I->click('#web');
        $I->waitForElementVisible('#web + .modulemenu-group-container .modulemenu-action');
        $I->seeElement('#web + .modulemenu-group-container .modulemenu-action');
    }

    /**
     * @param BackendTester $I
     */
    public function selectingAModuleDoesHighlightIt(BackendTester $I)
    {
        $I->seeNumberOfElements('#web + .modulemenu-group-container .modulemenu-action', [2, 20]);

        $I->wantTo('check that the second element has no "modulemenu-action-active" class\'');
        $I->cantSeeElement('#web_list.modulemenu-action-active');
        $I->click('#web_list');

        $I->wantTo('see that the second element has an "modulemenu-action-active" class');
        $I->canSeeElement('#web_list.modulemenu-action-active');
    }
}
