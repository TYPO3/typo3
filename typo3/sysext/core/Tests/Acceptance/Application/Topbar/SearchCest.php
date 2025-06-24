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

use Codeception\Util\Locator;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Test the search module in the top bar
 */
final class SearchCest
{
    private static string $toolbarItemSelector = '.t3js-toolbar-item-search';
    private static string $searchField = 'input[type="search"][name="query"]';
    private static string $searchResultContainer = 'typo3-backend-live-search-result-container';
    private static string $searchResultItem = 'typo3-backend-live-search-result-item';

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

        $I->canSee('admin', self::$searchResultItem);
        $I->click(self::$searchResultItem . ' [title~="admin"]+.livesearch-expand-action');
        $I->click(Locator::contains('typo3-backend-live-search-result-item-action', 'Edit'));

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

        $I->seeInField(self::$searchField, '');
    }
}
