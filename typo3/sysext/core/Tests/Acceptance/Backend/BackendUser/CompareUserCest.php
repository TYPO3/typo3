<?php
declare(strict_types = 1);

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Tests concerning the compare view of BE user module
 */
class CompareUserCest
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

        $I->see('Backend users');
        $I->click('Backend users');

        // switch to content iframe
        $I->switchToIFrame('list_frame');
        $I->waitForElementNotVisible('div#nprogess');
    }

    /**
     * @param Admin $I
     */
    public function editingBeUserRecordsFromCompareViewWorks(Admin $I)
    {
        // put two users into compare list
        $I->see('Backend User Listing');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-list');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(2) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-list-compare');
        $I->canSeeNumberOfElements('#typo3-backend-user-list-compare tbody tr', 2);
        $I->click('body > div > div.module-body.t3js-module-body > form:nth-child(4) > input');
        $I->waitForElementVisible('table.table-striped');

        // first user can be edited
        $usernameFirstCompare = $I->grabTextFrom('#tx_beuser_compare > thead > tr > th:nth-child(2)');
        $I->click('#tx_beuser_compare > thead > tr > th:nth-child(2) > a.btn.btn-default.pull-right');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "' . $usernameFirstCompare . '" on root level');

        // back to compare view
        $I->click('div.module-docheader .btn.t3js-editform-close');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Compare backend users', 'h1');

        // second user can be edited
        $usernameFirstCompare = $I->grabTextFrom('#tx_beuser_compare > thead > tr > th:nth-child(3)');
        $I->click('#tx_beuser_compare > thead > tr > th:nth-child(3) > a.btn.btn-default.pull-right');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "' . $usernameFirstCompare . '" on root level');
    }
}
