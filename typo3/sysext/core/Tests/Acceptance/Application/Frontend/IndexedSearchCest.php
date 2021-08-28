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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Frontend;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

class IndexedSearchCest
{
    protected string $sidebarSelector = '.sidebar.list-group';
    protected string $searchSelector = '#tx-indexedsearch-searchbox-sword';
    protected string $advancedSelector = '//a[contains(., "Advanced search")]';
    protected string $regularSelector = '//a[contains(., "Regular search")]';
    protected string $noResultsSelector = '.tx-indexedsearch-info-noresult';
    protected string $submitSelector = '.tx-indexedsearch-search-submit input[type=submit]';

    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });

        $I->scrollTo('//a[contains(., "list")]');
        $I->click('list', $this->sidebarSelector);
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeSearchResults(ApplicationTester $I): void
    {
        $I->fillField($this->searchSelector, 'search word');
        $I->click($this->submitSelector);
        $I->see('No results found.', $this->noResultsSelector);
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeAdvancedSearch(ApplicationTester $I): void
    {
        $seeElements = [
            '#tx-indexedsearch-selectbox-searchtype',
            '#tx-indexedsearch-selectbox-defaultoperand',
            '#tx-indexedsearch-selectbox-media',
            '#tx-indexedsearch-selectbox-lang',
            '#tx-indexedsearch-selectbox-sections',
            '#tx-indexedsearch-selectbox-freeIndexUid',
            '#tx-indexedsearch-selectbox-order',
            '#tx-indexedsearch-selectbox-desc',
            '#tx-indexedsearch-selectbox-results',
            '#tx-indexedsearch-selectbox-group',
        ];

        $I->fillField($this->searchSelector, 'search word');
        $I->click($this->advancedSelector);
        foreach ($seeElements as $element) {
            $I->seeElement($element);
        }

        $I->click($this->submitSelector);
        $I->see('No results found.', $this->noResultsSelector);

        $I->click($this->regularSelector);
        foreach ($seeElements as $element) {
            $I->dontSeeElement($element);
        }
    }
}
