<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Search;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Kasper;

/**
 * This testcase performs a search in the backend and checks if
 * no results get returned for the query "no results".
 */
class SearchNoResultCest
{
    public function _before(Kasper $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(Kasper $I)
    {
        $I->logout();
    }

    // tests
    public function tryToTest(Kasper $I)
    {
        $liveSearchToolBarItem = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem';
        $searchDropdownInfo = $liveSearchToolBarItem . ' > div > div > div > div.dropdown-info';
        $I->wantTo('Search "no" and check no result info');
        $I->fillField('#live-search-box', 'no result');
        // Using more than two letters to have better test stability on phantomjs
        $I->waitForElement($searchDropdownInfo);
        $dropdownHeader = $I->grabTextFrom($searchDropdownInfo);
        $I->assertEquals('No results found.', $dropdownHeader);
    }
}
