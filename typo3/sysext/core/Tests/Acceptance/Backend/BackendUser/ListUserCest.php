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
 * List User tests
 */
class ListUserCest
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
    public function showsHeadingAndListsBackendUsers(BackendTester $I)
    {
        $I->see('Backend User Listing');

        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact four Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 4);
    }

    /**
     * @param BackendTester $I
     */
    public function filterUsersByUsername(BackendTester $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', 4);

        $I->wantTo('Filter the list of user by valid username admin');
        $I->fillField('#tx_Beuser_username', 'admin');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact one fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 1);

        $I->wantTo('Filter the list of user by valid username administrator');
        $I->fillField('#tx_Beuser_username', 'administrator');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact no fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 0);
    }

    /**
     * @param BackendTester $I
     */
    public function filterUsersByAdmin(BackendTester $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', 4);

        $I->wantToTest('Filter BackendUser and see only admins');
        $I->selectOption('#tx_Beuser_usertype', 'Admin only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);

        $I->wantToTest('Filter BackendUser and see normal users');
        $I->selectOption('#tx_Beuser_usertype', 'Normal users only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    /**
     * @param BackendTester $I
     */
    public function filterUsersByStatus(BackendTester $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', 4);

        $I->wantToTest('Filter BackendUser and see only active users');
        $I->selectOption('#tx_Beuser_status', 'Active only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);

        $I->wantToTest('Filter BackendUser and see only inactive users');
        $I->selectOption('#tx_Beuser_status', 'Inactive only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    /**
     * @param BackendTester $I
     */
    public function filterUsersByLogin(BackendTester $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', 4);

        $I->wantToTest('Filter BackendUser and see only users logged in before');
        $I->selectOption('#tx_Beuser_logins', 'Logged in before');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);

        $I->wantToTest('Filter BackendUser and see only users never logged in before');
        $I->selectOption('#tx_Beuser_logins', 'Never logged in');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    /**
     * @param BackendTester $I
     */
    public function filterUsersByUserGroup(BackendTester $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', 4);

        // We expect exact one Backend Users created from the Fixtures has the usergroup named 'editor-group'
        $I->wantToTest('Filter BackendUser and see only users with given usergroup');
        $I->selectOption('#tx_beuser_backendUserGroup', 'editor-group');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact one fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 1);
    }

    /**
     * @param BackendTester $I
     */
    public function canEditUsersFromIndexListView(BackendTester $I)
    {
        $I->canSee('Backend User Listing', 'h1');
        $username = $I->grabTextFrom('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-title > a:nth-child(1) > b');

        $I->amGoingTo('test the edit button');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-control > div:nth-child(1) > a');
        $this->openAndCloseTheEditForm($I, $username);

        $I->amGoingTo('test the edit link on username');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-title > a:nth-child(1)');
        $this->openAndCloseTheEditForm($I, $username);

        $I->amGoingTo('test the edit link on real name');
        $I->click('#typo3-backend-user-list > tbody > tr:nth-child(1) > td.col-title > a:nth-child(4)');
        $this->openAndCloseTheEditForm($I, $username);
    }

    /**
     * @param BackendTester $I
     * @param int $countOfUsers
     */
    private function checkCountOfUsers(BackendTester $I, int $countOfUsers)
    {
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $countOfUsers);
        $I->wantToTest('If a number of users is shown in the footer row');
        $I->canSeeNumberOfElements('#typo3-backend-user-list tfoot tr', 1);
        $I->see($countOfUsers . ' Users', '#typo3-backend-user-list tfoot tr');
    }

    /**
     * @param BackendTester $I
     * @param string $username
     */
    private function openAndCloseTheEditForm(BackendTester $I, string $username): void
    {
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Edit Backend user "' . $username . '" on root level');

        $I->click('div.module-docheader .btn.t3js-editform-close');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Backend User Listing', 'h1');
    }
}
