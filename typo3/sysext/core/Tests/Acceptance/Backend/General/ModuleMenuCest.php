<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\General;

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
 * Module Menu tests
 */
class ModuleMenuCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();
    }

    /**
     * @param Admin $I
     */
    public function checkIfModuleMenuIsCollapsible(Admin $I)
    {
        // A sub-element of web module is shown
        $I->waitForElementVisible('#web .modulemenu-group-container .modulemenu-item');
        $I->seeElement('#web .modulemenu-group-container .modulemenu-item');

        // Collapse web module and verify sub elements are hidden
        $I->wantTo('collapse the menu element');
        $I->waitForElementVisible('#web .modulemenu-group-header');
        $I->click('#web .modulemenu-group-header');
        $I->waitForElementNotVisible('#web .modulemenu-group-container .modulemenu-item');
        $I->dontSeeElement('#web .modulemenu-group-container .modulemenu-item');

        // Expand again and verify sub elements are shown
        $I->wantTo('expand the menu element again');
        $I->click('#web .modulemenu-group-header');
        $I->waitForElementVisible('#web .modulemenu-group-container .modulemenu-item');
        $I->seeElement('#web .modulemenu-group-container .modulemenu-item');
    }

    /**
     * @param Admin $I
     */
    public function selectingAModuleDoesHighlightIt(Admin $I)
    {
        $I->seeNumberOfElements('#web .modulemenu-item-link', [2, 20]);

        $I->wantTo('check that the second element has no "active" class\'');
        $I->cantSeeElement('#web #web_list.active');
        $I->click('#web #web_list .modulemenu-item-link');

        $I->wantTo('see that the second element has an "active" class');
        $I->canSeeElement('#web #web_list.active');
    }
}
