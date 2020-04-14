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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\BackendUser;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Tests concerning the compare view of BE user module
 */
class CompareUserCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->see('Backend Users');
        $I->click('Backend Users');
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function editingBeUserRecordsFromCompareViewWorks(BackendTester $I)
    {
        // put two users into compare list
        $I->see('Backend User Listing');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-list');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(2) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-list-compare', 20);
        $I->canSeeNumberOfElements('#typo3-backend-user-list-compare tbody tr', 2);
        $I->click('body > div > div.module-body.t3js-module-body .compare');
        $I->waitForElementVisible('table.table-striped');

        // first user can be edited
        $usernameFirstCompare = $I->grabTextFrom('#tx_beuser_compare > thead > tr > th:nth-child(2)');
        $I->click('#tx_beuser_compare > thead > tr > th:nth-child(2) > a[title="edit"]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "' . $usernameFirstCompare . '" on root level');

        // back to compare view
        $I->click('.module-docheader a[title="Close"]');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Compare backend users', 'h1');

        // second user can be edited
        $usernameFirstCompare = $I->grabTextFrom('#tx_beuser_compare > thead > tr > th:nth-child(3)');
        $I->click('#tx_beuser_compare > thead > tr > th:nth-child(3) > a[title="edit"]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "' . $usernameFirstCompare . '" on root level');
    }
}
