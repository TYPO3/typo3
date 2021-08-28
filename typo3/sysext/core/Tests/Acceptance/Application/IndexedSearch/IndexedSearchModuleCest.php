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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\IndexedSearch;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests for the Indexed Search module
 */
class IndexedSearchModuleCest
{
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
    public function checkExpectedTextOnIndexedSearchPages(ApplicationTester $I)
    {
        $I->click('#web_IndexedSearchIsearch');
        $I->waitForElement('svg .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_0 text.node-name');
        $I->switchToContentFrame();
        $I->seeElement('.t3-js-jumpMenuBox');
        $I->selectOption('.t3-js-jumpMenuBox', 'General statistics');
        $I->see('Indexing Engine Statistics', '.t3js-module-body');
        $I->see('General statistics', '.t3js-module-body');
        $I->see('Row count by database table', '.t3js-module-body');
        // Select only "Row count by database table"
        $rowCount = $I->grabMultiple('.row > .col-md-6:first-child > table > tbody >tr > td:nth-child(2)');
        foreach ($rowCount as $count) {
            // Check only for numeric value, coz we can't actually predict the value due to frontend testing
            $I->assertIsNumeric($count);
        }

        $I->selectOption('.t3-js-jumpMenuBox', 'List: Pages');
        $I->see('Indexing Engine Statistics', '.t3js-module-body');
        $I->see('Pages', '.t3js-module-body');

        $I->selectOption('.t3-js-jumpMenuBox', 'List: External documents');
        $I->see('Indexing Engine Statistics', '.t3js-module-body');
        $I->see('External documents', '.t3js-module-body');

        $I->selectOption('.t3-js-jumpMenuBox', 'Detailed statistics');
        $I->see('Indexing Engine Statistics', '.t3js-module-body');
        $I->see('Please select a page from the page tree.', '.t3js-module-body');
    }
}
