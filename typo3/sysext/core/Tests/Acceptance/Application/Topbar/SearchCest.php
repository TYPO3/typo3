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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Test the search module in the top bar
 */
class SearchCest
{
    public static string $toolbarItemSelector = '.t3js-toolbar-item-search';
    public static string $searchField = 'input[type="search"][name="searchField"]';
    public static string $searchResultContainer = 'typo3-backend-live-search-result-container';
    public static string $searchResultItem = 'typo3-backend-live-search-result-item';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function searchAndTestIfAutocompletionWorks(ApplicationTester $I, ModalDialog $dialog): void
    {
        $I->cantSeeElement(self::$searchField);
        $I->click(self::$toolbarItemSelector);
        $dialog->canSeeDialog();

        $I->fillField(self::$searchField, 'adm');

        $I->canSee('Backend user', self::$searchResultItem);
        $I->click(self::$searchResultItem . ' [title~="admin"]');

        $I->switchToContentFrame();
        $I->waitForElementVisible('#EditDocumentController');
        $I->canSee('Edit Backend user "admin" on root level');
    }

    public function searchForFancyTextAndCheckEmptyResultInfo(ApplicationTester $I, ModalDialog $dialog): void
    {
        $I->click(self::$toolbarItemSelector);
        $dialog->canSeeDialog();

        $I->fillField(self::$searchField, 'Kasper = Jesus # joh316');

        // todo: check why TYPO3 does not return a result for "Kasper" by itself
        $I->canSee('No results found.', 'div.alert');

        $I->pressKey(self::$searchField, WebDriverKeys::ESCAPE);

        $I->waitForElementNotVisible(self::$searchResultContainer);
        $I->cantSee(self::$searchField);
    }

    public function checkIfTheShowAllLinkPointsToTheListViewWithSearchResults(ApplicationTester $I, ModalDialog $dialog): void
    {
        $I->click(self::$toolbarItemSelector);
        $dialog->canSeeDialog();

        $I->fillField(self::$searchField, 'fileadmin');

        $I->canSee('fileadmin', self::$searchResultItem);
        $I->click('Show All', 'typo3-backend-live-search');
        $I->waitForElementNotVisible(self::$searchResultContainer);

        $I->switchToContentFrame();

        // Search word is transferred to the recordlist search form
        $I->seeInField('#search_field', 'fileadmin');

        // Correct table and element is displayed
        $I->waitForElementVisible('form[name="list-table-form-sys_file_storage"]');
        $I->canSee('fileadmin', 'form[name="list-table-form-sys_file_storage"] a');
    }
}
