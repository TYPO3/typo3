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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;

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
    }

    /**
     * @param Admin $I
     */
    public function checkIfModuleMenuIsCollapsible(Admin $I)
    {
        foreach (['#web', '#system'] as $moduleGroupId) {
            $I->seeElement($moduleGroupId . ' .typo3-module-menu-group-container .typo3-module-menu-item');

            $I->wantTo('collapse the menu element');
            $I->click($moduleGroupId . ' .typo3-module-menu-group-header');

            $I->waitForElementNotVisible($moduleGroupId . ' .typo3-module-menu-group-container .typo3-module-menu-item');

            $I->wantTo('expand the menu element again');
            $I->click($moduleGroupId . ' .typo3-module-menu-group-header');

            $I->seeElement($moduleGroupId . ' .typo3-module-menu-group-container .typo3-module-menu-item');
        }
    }

    /**
     * @param Admin $I
     */
    public function selectingAModuleDoesHighlightIt(Admin $I)
    {
        $I->seeNumberOfElements('#web .typo3-module-menu-item-link', [2,20]);

        $I->wantTo('check that the second element has no "active" class\'');
        $I->cantSeeElement('#web #web_list.active');
        $I->click('#web #web_list .typo3-module-menu-item-link');

        $I->wantTo('see that the second element has an "active" class');
        $I->canSeeElement('#web #web_list.active');
    }
}
