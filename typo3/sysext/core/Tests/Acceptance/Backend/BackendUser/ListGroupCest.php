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
 * Tests concerning the listing of BeUser groups
 */
class ListGroupCest
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
        $I->selectOption('div.module-docheader  select.t3-js-jumpMenuBox', 'Backend user groups');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Backend User Group Listing', 'h1');
    }

    public function canEditBeGroupsFromListView(Admin $I)
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
     * @param Admin $I
     */
    public function canEditSubGroupFromListView(Admin $I)
    {
        $I->amGoingTo('test the subgroup edit form');
        $groupname = $I->grabTextFrom('table.table-striped > tbody > tr:nth-child(2) > td:nth-child(3) > a:nth-child(1)');
        $I->click('table.table-striped > tbody > tr:nth-child(2) > td:nth-child(3) > a:nth-child(1)');
        $this->openAndCloseTheEditForm($I, $groupname);
    }

    /**
     * @param Admin $I
     * @param string $groupname
     */
    private function openAndCloseTheEditForm(Admin $I, string $groupname): void
    {
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Edit Backend usergroup "' . $groupname . '" on root level');

        $I->click('div.module-docheader .btn.t3js-editform-close');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Backend User Group Listing', 'h1');
    }
}
