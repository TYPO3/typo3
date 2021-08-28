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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Topbar;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\TestingFramework\Core\Acceptance\Helper\Topbar;

/**
 * Test the search module in the top bar
 */
class SearchCest
{
    /**
     * Selector for the module container in the topbar
     *
     * @var string
     */
    public static $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem';

    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param ApplicationTester $I
     */
    public function searchAndTestIfAutocompletionWorks(ApplicationTester $I)
    {
        $I->cantSeeElement(self::$topBarModuleSelector . ' ' . Topbar::$dropdownListSelector);
        $I->fillField('#live-search-box', 'adm');
        $I->waitForElementVisible(self::$topBarModuleSelector . ' ' . Topbar::$dropdownListSelector);

        $I->canSee('Backend user', self::$topBarModuleSelector);
        $I->click('admin', self::$topBarModuleSelector);

        $I->switchToContentFrame();
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "admin" on root level');
    }

    /**
     * @param ApplicationTester $I
     */
    public function searchForFancyTextAndCheckEmptyResultInfo(ApplicationTester $I)
    {
        $I->fillField('#live-search-box', 'Kasper = Jesus # joh316');
        $I->waitForElementVisible(self::$topBarModuleSelector . ' ' . Topbar::$dropdownListSelector, 100);

        // tod0: check why TYPO3 does not return a result for "Kasper" by itself
        $I->canSee('No results found.', self::$topBarModuleSelector);

        $I->click(self::$topBarModuleSelector . ' .close');
        $I->waitForElementNotVisible(self::$topBarModuleSelector . ' ' . Topbar::$dropdownListSelector, 100);
        $I->cantSeeInField('#live-search-box', 'Kasper = Jesus # joh316');
    }

    /**
     * @param ApplicationTester $I
     */
    public function checkIfTheShowAllLinkPointsToTheListViewWithSearchResults(ApplicationTester $I)
    {
        $I->fillField('#live-search-box', 'fileadmin');
        $I->waitForElementVisible(self::$topBarModuleSelector . ' ' . Topbar::$dropdownListSelector);

        $I->canSee('fileadmin', self::$topBarModuleSelector);
        $I->click('.t3js-live-search-show-all', self::$topBarModuleSelector);

        $I->switchToContentFrame();

        // Search word is transferred to the recordlist search form
        $I->seeInField('#search_field', 'fileadmin');

        // Correct table and element is displayed
        $I->waitForElementVisible('form[name="list-table-form-sys_file_storage"]');
        $I->canSee('fileadmin', 'form[name="list-table-form-sys_file_storage"] a');
    }
}
