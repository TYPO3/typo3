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

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Tests concerning the listing of BeUser groups
 */
class ListGroupCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->see('Backend users');
        $I->click('Backend users');

        $I->switchToContentFrame();
        $I->selectOption('div.module-docheader select.t3-js-jumpMenuBox', 'Backend user groups');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Backend User Group Listing', 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function canEditBeGroupsFromListView(BackendTester $I)
    {
        $groupname = $I->grabTextFrom('table.table-striped > tbody > tr:nth-child(1) > td.col-title > a > b');

        $I->amGoingTo('test edit on group name');
        $I->click('table.table-striped > tbody > tr:nth-child(1) > td.col-title > a');
        $this->openAndCloseTheEditForm($I, $groupname);

        $I->amGoingTo('test edit on edit button');
        $I->click('table.table-striped > tbody > tr:nth-child(1) > td.col-control > div:nth-child(1) > a:nth-child(1)');
        $this->openAndCloseTheEditForm($I, $groupname);
    }

    /**
     * @param BackendTester $I
     */
    public function canEditSubGroupFromListView(BackendTester $I)
    {
        $I->amGoingTo('test the subgroup edit form');
        $groupname = $I->grabTextFrom('table.table-striped > tbody > tr:nth-child(2) > td:nth-child(3) > a:nth-child(1)');
        $I->click('table.table-striped > tbody > tr:nth-child(2) > td:nth-child(3) > a:nth-child(1)');
        $this->openAndCloseTheEditForm($I, $groupname);
    }

    /**
     * @param BackendTester $I
     * @param string $groupName
     */
    private function openAndCloseTheEditForm(BackendTester $I, string $groupName): void
    {
        $I->waitForText('Edit Backend usergroup "' . $groupName . '" on root level', 120);
        $I->see('Edit Backend usergroup "' . $groupName . '" on root level', 'h1');

        $I->click('div.module-docheader .btn.t3js-editform-close');
        $I->waitForElementVisible('table.table-striped');
        $I->waitForText('Backend User Group Listing', 120);
        $I->see('Backend User Group Listing', 'h1');
    }
}
