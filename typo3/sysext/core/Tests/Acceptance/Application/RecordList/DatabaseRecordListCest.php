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
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo', 'displaycond']);
        $I->switchToContentFrame();
    }

    public function allRecordsCanBeSeen(ApplicationTester $I): void
    {
        $I->wantToTest('whether all records can be seen by default in the record list');
        $I->click('Language');
        $I->click('All languages');
        self::checkRowVisibility($I, ['1', '2', '3', '4', '5']);
    }

    public function recordListCanBeFilteredByLanguage(ApplicationTester $I): void
    {
        $I->wantToTest('whether the record list can be filtered by language');

        // Default language
        $I->amGoingTo('select the default language');
        $I->click('Language');
        $I->click('English');
        self::checkRowVisibility($I, ['1'], ['2', '3', '4', '5']);

        // Language with records having l10n_parent
        $I->amGoingTo('select a language with records having l10n_parent');
        $I->click('Language');
        $I->click('styleguide demo language german');
        self::checkRowVisibility($I, ['1', '3'], ['2', '4', '5']);

        // Language with records not having l10n_parent
        $I->amGoingTo('select a language with records not having l10n_parent');
        $I->click('Language');
        $I->click('styleguide demo language danish');
        self::checkRowVisibility($I, ['1', '2'], ['3', '4', '5']);
    }

    public function searchKeepsLanguageFilter(ApplicationTester $I): void
    {
        $I->wantToTest('whether the search keeps the language filter in the record list');

        // Show search form
        $I->amGoingTo('show the search form');
        $I->click('View', self::$docHeader);
        $I->click('Show search', self::$docHeader);

        // Filter language
        $I->amGoingTo('select the default language');
        $I->click('Language');
        $I->click('styleguide demo language danish');

        // Enter a search term
        $I->amGoingTo('enter a search term');
        $I->fillField('searchTerm', '2');
        $I->click('button[name="search"]', '.recordsearchbox-container');

        $I->seeElementInDOM(self::$docHeader . ' .icon-flags-dk');
        self::checkRowVisibility($I, ['2'], ['1', '3', '4', '5']);
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
}
