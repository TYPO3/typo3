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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\DbCheck;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Tests concerning Reports Module
 */
class DbCheckModuleCest
{
    protected static string $defaultDashboardTitle = 'My Dashboard';

    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('#system_dbint');
        $I->switchToContentFrame();
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeOverview(ApplicationTester $I): void
    {
        $I->see('Database integrity check', 'h1');
        $I->see('Records Statistics', 'a');
        $I->see('Relations', 'a');
        $I->see('Search', 'a');
        $I->see('Check and update global reference index', 'a');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeRecordStatistics(ApplicationTester $I): void
    {
        $this->goToPageAndSeeHeadline($I, 'Record Statistics', 'Records Statistics');

        foreach ($this->recordStatisticsDataProvider() as $statistic) {
            $count = $this->getCountByRowName($I, $statistic['name'])->getText();
            // Use >= here to make sure we don't get in trouble with other tests creating db entries
            $I->assertGreaterThanOrEqual($statistic['count'], $count);
        }
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeDatabaseRelations(ApplicationTester $I): void
    {
        $this->goToPageAndSeeHeadline($I, 'Database Relations', 'Relations');
        $I->see('Select fields', 'h2');
        $I->see('Group fields', 'h2');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeFullSearch(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $this->goToPageAndSeeHeadline($I, 'Full search', 'Search whole Database');
        $I->see('Search options', 'h2');
        $I->see('Result', 'h2');

        // Fill in search phrase and check results
        $I->fillField('input[name="SET[sword]"]', 'styleguide demo group 1');
        $I->click('Search All Records');
        $I->see('styleguide demo group 1', 'td');
        $I->dontSee('styleguide demo group 2', 'td');

        // Open info modal and see text in card
        $I->click('a[data-dispatch-args-list]');
        $modalDialog->canSeeDialog();
        $I->switchToIFrame('.modal-iframe');
        $I->see('styleguide demo group 1', '.card-title');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeManageReferenceIndex(ApplicationTester $I): void
    {
        $this->goToPageAndSeeHeadline($I, 'Manage Reference Index', 'Manage Reference Index');

        $I->click('Check reference index');
        $I->waitForElement('.alert');

        $I->click('Update reference index');
        $I->waitForElement('.alert');

        $I->click('Check reference index');
        $I->waitForElement('.alert-success');
        $I->see('Index Integrity was perfect!', '.alert-success');

        $I->click('Update reference index');
        $I->see('Index Integrity was perfect!', '.alert-success');
    }

    /**
     * @return array[]
     */
    protected function recordStatisticsDataProvider(): array
    {
        return [
            [
                'name' => 'Total number of default language pages',
                'count' => 84,
            ],
            [
                'name' => 'Total number of translated pages',
                'count' => 132,
            ],
            [
                'name' => 'Marked-deleted pages',
                'count' => 0,
            ],
            [
                'name' => 'Hidden pages',
                'count' => 1,
            ],
            [
                'name' => 'Standard',
                'count' => 1,
            ],
            [
                'name' => 'Backend User Section',
                'count' => 0,
            ],
            [
                'name' => 'Link to External URL',
                'count' => 0,
            ],
        ];
    }

    /**
     * @param string $select
     * @param string $headline
     * @param ApplicationTester $I
     */
    protected function goToPageAndSeeHeadline(ApplicationTester $I, string $select, string $headline): void
    {
        $I->selectOption('select[name=DatabaseJumpMenu]', $select);
        $I->see($headline, 'h1');
    }

    /**
     * Find count of table row by name
     *
     * @param ApplicationTester $I
     * @param string $fieldLabel
     * @return RemoteWebElement
     */
    protected function getCountByRowName(ApplicationTester $I, string $rowName, int $sibling = 1): RemoteWebElement
    {
        $I->comment('Get context for table row "' . $rowName . '"');
        return $I->executeInSelenium(
            static function (RemoteWebDriver $webDriver) use ($rowName, $sibling) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//td[contains(text(),"' . $rowName . '")]/following-sibling::td[' . $sibling . ']'
                    )
                );
            }
        );
    }
}
