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

use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * List User tests
 */
final class ListUserCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');

        $I->see('Backend Users');
        $I->click('Backend Users');

        $I->switchToContentFrame();
    }

    public function showsHeadingAndListsBackendUsers(ApplicationTester $I, Scenario $scenario): void
    {
        $I->see('Backend users');

        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');

        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        // We expect exactly four Backend Users to have been created by the fixtures
        $expectedUsers = 4;
        if ($isComposerMode) {
            // User _cli_ will additionally be available in composer mode, created
            // by execution of `vendor/bin/typo3` CLI in setup script.
            $expectedUsers++;
        }
        $this->checkCountOfUsers($I, $expectedUsers);
    }

    public function filterUsersByUsername(ApplicationTester $I, Scenario $scenario): void
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        $expectedUsers = 4;
        if ($isComposerMode) {
            $expectedUsers++;
        }
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $expectedUsers);

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

    public function filterUsersByAdmin(ApplicationTester $I, Scenario $scenario): void
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        $expectedUsers = 4;
        if ($isComposerMode) {
            $expectedUsers++;
        }
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $expectedUsers);

        $I->wantToTest('Filter BackendUser and see only admins');
        $I->selectOption('#tx_Beuser_usertype', 'Admin only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two (composer-mode: three) fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2 + ($isComposerMode ? 1 : 0));

        $I->wantToTest('Filter BackendUser and see normal users');
        $I->selectOption('#tx_Beuser_usertype', 'Normal users only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    public function filterUsersByStatus(ApplicationTester $I, Scenario $scenario): void
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        $expectedUsers = 4;
        if ($isComposerMode) {
            $expectedUsers++;
        }
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $expectedUsers);

        $I->wantToTest('Filter BackendUser and see only active users');
        $I->selectOption('#tx_Beuser_status', 'Active only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two (composer-mode three) fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2 + ($isComposerMode ? 1 : 0));

        $I->wantToTest('Filter BackendUser and see only inactive users');
        $I->selectOption('#tx_Beuser_status', 'Inactive only');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    public function filterUsersByLogin(ApplicationTester $I, Scenario $scenario): void
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        $expectedUsers = 4;
        if ($isComposerMode) {
            $expectedUsers++;
        }
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $expectedUsers);

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

        // We expect exact two (composer-mode three) fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2 + ($isComposerMode ? 1 : 0));
    }

    public function filterUsersByUserGroup(ApplicationTester $I, Scenario $scenario): void
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        $expectedUsers = 4;
        if ($isComposerMode) {
            $expectedUsers++;
        }
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $expectedUsers);

        // We expect exact one Backend Users created from the Fixtures has the usergroup named 'editor-group'
        $I->wantToTest('Filter BackendUser and see only users with given usergroup');
        $I->selectOption('#tx_beuser_backendUserGroup', 'editor-group');
        $I->click('Filter');
        $I->waitForElementNotVisible('div#nprogess');
        $I->waitForElementVisible('#typo3-backend-user-list');

        // We expect exact one fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 1);
    }

    public function canEditUsersFromIndexListView(ApplicationTester $I): void
    {
        $I->canSee('Backend users', 'h1');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $I->click('button[value="reset-filters"]');
        $I->waitForElementVisible('#typo3-backend-user-list');
        $username = 'admin';
        $adminRow = '//*[@id="typo3-backend-user-list"]//tr[contains(td[2]/a[1]/b[1], "' . $username . '")]';

        $I->amGoingTo('test the edit button');
        $I->click($adminRow . '//div[@role="group"]/a[@title="Edit"]');
        $this->openAndCloseTheEditForm($I, $username);

        $I->amGoingTo('test the edit link on username');
        $I->click($adminRow . '//td[@class="col-title"]/a[1]');
        $this->openAndCloseTheEditForm($I, $username);

        $I->amGoingTo('test the edit link on real name');
        $I->click($adminRow . '//td[@class="col-title"]/a[2]');
        $this->openAndCloseTheEditForm($I, $username);
    }

    private function checkCountOfUsers(ApplicationTester $I, int $countOfUsers): void
    {
        $I->canSeeNumberOfElements('#typo3-backend-user-list tbody tr', $countOfUsers);
        $I->wantToTest('If a number of users is shown in the footer row');
        $I->canSeeNumberOfElements('#typo3-backend-user-list tfoot tr', 1);
        $I->see($countOfUsers . ' User', '#typo3-backend-user-list tfoot tr');
    }

    private function openAndCloseTheEditForm(ApplicationTester $I, string $username): void
    {
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Edit Backend user "' . $username . '" on root level');

        $I->click('div.module-docheader .btn.t3js-editform-close');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Backend users', 'h1');
    }
}
