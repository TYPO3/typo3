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
final class IndexedSearchModuleCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkExpectedTextOnIndexedSearchPages(ApplicationTester $I): void
    {
        $I->click('[data-modulemenu-identifier="manage_search_index"]');
        // click on PID=0
        $I->clickWithLeftButton('#typo3-pagetree-treeContainer [role="treeitem"][data-id="0"] .node-contentlabel');
        $I->switchToContentFrame();
        // Click the module actions dropdown button and select "General statistics"
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click('General statistics', '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->see('General statistics', '.t3js-module-body');
        $I->see('Row count by database table', '.t3js-module-body');
        // Select only "Row count by database table"
        $rowCount = $I->grabMultiple('.row > .col-md-6:first-child > table > tbody >tr > td:nth-child(2)');
        foreach ($rowCount as $count) {
            // Check only for numeric value, coz we can't actually predict the value due to frontend testing
            $I->assertIsNumeric($count);
        }

        // Click the module actions dropdown button and select "List of indexed pages"
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click('List of indexed pages', '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->see('List of indexed pages', '.t3js-module-body');

        // Click the module actions dropdown button and select "List of indexed external documents"
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click('List of indexed external documents', '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->see('List of indexed external documents', '.t3js-module-body');

        // Click the module actions dropdown button and select "Detailed statistics"
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click('Detailed statistics', '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->see('Detailed statistics', '.t3js-module-body');
        $I->see('Please select a page in the page tree.', '.t3js-module-body');
    }
}
