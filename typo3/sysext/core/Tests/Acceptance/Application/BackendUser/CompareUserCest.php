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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\BackendUser;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests concerning the compare view of BE user module
 */
final class CompareUserCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');

        $I->see('Backend Users');
        $I->click('Backend Users');
        $I->switchToContentFrame();
    }

    public function editingBeUserRecordsFromCompareViewWorks(ApplicationTester $I): void
    {
        // put two users into compare list
        $I->see('Backend users');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-list');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(2) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-list-compare', 20);
        $I->canSeeNumberOfElements('#typo3-backend-user-list-compare tbody tr', 2);
        $I->click('body > div > div.module-body.t3js-module-body .t3js-acceptance-compare');
        $I->waitForElementVisible('table.table-striped-columns');

        // first user can be edited
        $usernameFirstCompare = $I->grabTextFrom('table.beuser-comparison-table > thead > tr > th:nth-child(2) .beuser-comparison-element__title > span:nth-child(2)');
        $I->click('table.beuser-comparison-table > thead > tr > th:nth-child(2) a[title="Edit"]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "' . trim(explode('[', $usernameFirstCompare)[0]) . '" on root level');

        // back to compare view
        $I->click('.module-docheader a[title="Close"]');
        $I->waitForElementVisible('table.table-striped-columns');
        $I->canSee('Compare backend users', 'h1');

        // second user can be edited
        $usernameFirstCompare = $I->grabTextFrom('table.beuser-comparison-table > thead > tr > th:nth-child(3) .beuser-comparison-element__title > span:nth-child(2)');
        $I->click('table.beuser-comparison-table > thead > tr > th:nth-child(3) a[title="Edit"]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "' . trim(explode('[', $usernameFirstCompare)[0]) . '" on root level');
    }

    public function accessingBackendUserGroupCompareViewWorks(ApplicationTester $I): void
    {
        $I->amGoingTo('Switch to user group listing');
        $I->see('Backend users', 'h1');
        $I->selectOption('.t3-js-jumpMenuBox', 'Backend user groups');
        $I->see('Backend user groups', 'h1');

        $I->amGoingTo('Add three groups to compare');
        $I->click('#typo3-backend-user-group-list > tbody > tr:nth-child(1) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-group-list');
        $I->click('#typo3-backend-user-group-list > tbody > tr:nth-child(2) > td.col-control > div:nth-child(3) > a');
        $I->waitForElementVisible('table#typo3-backend-user-group-list');
        $I->click('#typo3-backend-user-group-list > tbody > tr:nth-child(3) > td.col-control > div:nth-child(3) > a');

        $I->amGoingTo('Access the user group compare view');
        $I->waitForElementVisible('table#typo3-backend-user-list-compare', 20);
        $I->canSeeNumberOfElements('#typo3-backend-user-list-compare tbody tr', 3);
        $I->click('Compare selected backend user groups');

        $I->amGoingTo('Check compare view is loaded with the correct number of groups');
        $I->see('Compare backend user groups', 'h1');
        $I->canSeeNumberOfElements('table.beuser-comparison-table > thead > tr > th', 3);
    }
}
