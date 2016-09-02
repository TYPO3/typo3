<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\BackendUser;

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
 * Search User tests
 */
class ListUserCest
{
    /**
     * @param Admin $I
     * @return void
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();

        $I->see('Backend users');
        $I->click('Backend users');

        // switch to content iframe
        $I->switchToIFrame('content');
    }

    /**
     * @param Admin $I
     * @return void
     */
    public function showsHeadingAndListsBackendUsers(Admin $I)
    {
        $I->see('Backend User Listing');

        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', 4);

        $I->wantToTest('If a number of users is shown in the footer row');
        $I->canSeeNumberOfElements('#typo3-backend-user-list tfoot tr', 1);
        $I->see('4 Users', '#typo3-backend-user-list tfoot tr');
    }
}
