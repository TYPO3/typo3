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

use Codeception\Example;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Tests concerning Reports Module
 */
final class DbCheckModuleCest
{
    private static string $defaultDashboardTitle = 'My Dashboard';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('#system_dbint');
        $I->switchToContentFrame();
    }

    private function recordStatisticsDataProvider(): array
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
     * @dataProvider recordStatisticsDataProvider
     */
    public function seeRecordStatistics(ApplicationTester $I, Example $testData): void
    {
        $this->goToPageAndSeeHeadline($I, 'Record Statistics', 'Records Statistics');

        $count = $this->getCountByRowName($I, $testData['name'])->getText();
        // Use >= here to make sure we don't get in trouble with other tests creating db entries
        $I->assertGreaterThanOrEqual($testData['count'], $count);
    }

    public function seeDatabaseRelations(ApplicationTester $I): void
    {
        $this->goToPageAndSeeHeadline($I, 'Database Relations', 'Relations');
        $I->see('Select fields', 'h2');
        $I->see('Group fields', 'h2');
    }

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

    public function seeManageReferenceIndex(ApplicationTester $I): void
    {
        $this->goToPageAndSeeHeadline($I, 'Manage Reference Index', 'Manage Reference Index');

        $I->click('Check reference index');
        $I->waitForElement('.callout-warning');

        $I->click('Update reference index');
        $I->waitForElement('.callout-warning');

        $I->click('Check reference index');
        $I->waitForElement('.callout-success');
        $I->see('Index integrity was perfect!', '.callout-success');

        $I->click('Update reference index');
        $I->waitForElement('.callout-success');
        $I->see('Index integrity was perfect!', '.callout-success');
    }

    private function goToPageAndSeeHeadline(ApplicationTester $I, string $select, string $headline): void
    {
        $I->selectOption('select[name=DatabaseJumpMenu]', $select);
        $I->see($headline, 'h1');
    }

    /**
     * Find count of table row by name
     */
    private function getCountByRowName(ApplicationTester $I, string $rowName, int $sibling = 1): RemoteWebElement
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
