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
    public static string $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem';
    public static $dropdownListSelector = '.t3js-toolbar-item-search.toolbar-item-search-field-dropdown';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function searchAndTestIfAutocompletionWorks(ApplicationTester $I): void
    {
        // .t3js-toolbar-item-search.toolbar-item-search-field-dropdown
        $I->cantSeeElement(self::$dropdownListSelector);
        $I->fillField('#live-search-box', 'adm');
        $I->waitForElementVisible(self::$dropdownListSelector);

        $I->canSee('Backend user', self::$dropdownListSelector);
        $I->click('admin', self::$dropdownListSelector);

        $I->switchToContentFrame();
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "admin" on root level');
    }

    public function searchForFancyTextAndCheckEmptyResultInfo(ApplicationTester $I): void
    {
        $I->fillField('#live-search-box', 'Kasper = Jesus # joh316');
        $I->waitForElementVisible(self::$dropdownListSelector, 100);

        // tod0: check why TYPO3 does not return a result for "Kasper" by itself
        $I->canSee('No results found.', self::$dropdownListSelector);

        $I->click(self::$topBarModuleSelector . ' .close');
        $I->waitForElementNotVisible(self::$dropdownListSelector, 100);
        $I->cantSeeInField('#live-search-box', 'Kasper = Jesus # joh316');
    }

    public function checkIfTheShowAllLinkPointsToTheListViewWithSearchResults(ApplicationTester $I): void
    {
        $I->fillField('#live-search-box', 'fileadmin');
        $I->waitForElementVisible(self::$dropdownListSelector);

        $I->canSee('fileadmin', self::$dropdownListSelector);
        $I->click('.t3js-live-search-show-all', self::$dropdownListSelector);

        $I->switchToContentFrame();

        // Search word is transferred to the recordlist search form
        $I->seeInField('#search_field', 'fileadmin');

        // Correct table and element is displayed
        $I->waitForElementVisible('form[name="list-table-form-sys_file_storage"]');
        $I->canSee('fileadmin', 'form[name="list-table-form-sys_file_storage"] a');
    }
}
