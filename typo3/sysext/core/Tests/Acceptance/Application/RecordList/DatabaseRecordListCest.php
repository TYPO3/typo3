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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\RecordList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Cases concerning the record listing functionality
 */
final class DatabaseRecordListCest
{
    private static string $dataTable = 'table[data-table="tx_styleguide_displaycond"]';
    private static string $docHeader = '.module-docheader';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->amGoingTo('list all records');
        $I->click('Records');
        $I->waitForElementNotVisible('typo3-backend-progress-bar');
        $pageTree->openPath(['styleguide TCA demo', 'displaycond']);
        $I->switchToContentFrame();
        // We check all (possible "remaining") languages and uncheck them again
        // to always have a clean state for every test (only default selected).
        self::toggleAllLanguages($I);
        self::toggleAllLanguages($I, false);
    }

    public function recordListCanBeFilteredByLanguage(ApplicationTester $I): void
    {
        $I->wantToTest('whether the record list can be filtered by language');

        // Default language only (default is always selected and cannot be deselected)
        $I->amGoingTo('verify only the default language records are shown');
        $I->click('.module-docheader-navigation button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-navigation .dropdown-menu');
        // Verify default language toggle is active (always selected)
        $I->seeElement('.module-docheader-navigation .dropdown-menu [data-dropdowntoggle-status="active"][title*="Default language is always shown"]');
        $I->click('.module-docheader-navigation button.dropdown-toggle'); // Close dropdown
        self::checkRowVisibility($I, ['1'], ['2', '3', '4', '5']);

        // Language with records having l10n_parent
        $I->amGoingTo('add a language with records having l10n_parent');
        $I->click('.module-docheader-navigation button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-navigation .dropdown-menu');
        $I->click('styleguide demo language german', '.module-docheader-navigation .dropdown-menu');
        self::checkRowVisibility($I, ['1', '3'], ['2', '4', '5']);

        // Language with records not having l10n_parent
        $I->amGoingTo('add a language with records not having l10n_parent');
        $I->click('.module-docheader-navigation button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-navigation .dropdown-menu');
        $I->click('styleguide demo language danish', '.module-docheader-navigation .dropdown-menu');
        self::checkRowVisibility($I, ['1', '3', '2'], ['4', '5']);

        // Check all languages
        $I->amGoingTo('add all languages');
        self::toggleAllLanguages($I);
        self::checkRowVisibility($I, ['1', '2', '3', '4', '5']);

        // Uncheck all languages
        $I->amGoingTo('uncheck all languages');
        self::toggleAllLanguages($I, false);
        self::checkRowVisibility($I, ['1'], ['2', '3', '4', '5']);
    }

    public function searchKeepsLanguageFilter(ApplicationTester $I): void
    {
        $I->wantToTest('whether the search keeps the language filter in the record list');

        // Show search form
        $I->amGoingTo('show the search form');
        $I->click('.module-docheader-column button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-column .dropdown-menu');
        $I->click('Show search', '.module-docheader-column .dropdown-menu');

        // Filter language
        $I->amGoingTo('select the default language');
        $I->click('.module-docheader-navigation button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-navigation .dropdown-menu');
        $I->click('styleguide demo language danish', '.module-docheader-navigation .dropdown-menu');

        // Enter a search term
        $I->amGoingTo('enter a search term');
        $I->fillField('searchTerm', '2');
        $I->click('button[name="search"]', '.recordsearchbox-container');

        $I->seeElementInDOM(self::$docHeader . ' .icon-flags-dk');
        self::checkRowVisibility($I, ['1', '2'], ['3', '4', '5']);
    }

    private static function checkRowVisibility(ApplicationTester $I, array $mustSee, array $mustNotSee = []): void
    {
        $I->waitForElement(self::$dataTable);

        foreach ($mustSee as $value) {
            $I->canSee($value, self::$dataTable);
        }
        if (!empty($mustNotSee)) {
            foreach ($mustNotSee as $value) {
                $I->cantSee($value, self::$dataTable);
            }
        }
    }

    private static function toggleAllLanguages(ApplicationTester $I, bool $check = true): void
    {
        $I->click('.module-docheader-navigation button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-navigation .dropdown-menu');
        $I->click($check ? 'Check all' : 'Uncheck all', '.module-docheader-navigation .dropdown-menu');
    }
}
